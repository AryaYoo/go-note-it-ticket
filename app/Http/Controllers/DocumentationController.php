<?php

namespace App\Http\Controllers;

use App\Models\DocumentationChat;
use App\Models\DocumentationConversation;
use App\Services\DocumentationChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentationController extends Controller
{
    public function __construct(private DocumentationChatService $chat) {}

    public function index(Request $request)
    {
        $userId = Auth::id();

        // Ambil percakapan aktif jika diminta, atau percakapan terbaru
        $activeConversation = null;
        if ($request->filled('conversation_id')) {
            $activeConversation = DocumentationConversation::where('user_id', $userId)
                ->where('id', $request->conversation_id)
                ->first();
        } else {
            $activeConversation = DocumentationConversation::where('user_id', $userId)
                ->latest('updated_at')
                ->first();
        }

        $chats = $activeConversation
            ? $activeConversation->messages()->orderBy('created_at', 'asc')->get()
            : collect();

        $conversations = DocumentationConversation::where('user_id', $userId)
            ->withCount('messages')
            ->latest('updated_at')
            ->get();

        return view('documentation.index', compact('activeConversation', 'chats', 'conversations'));
    }

    public function chat(Request $request)
    {
        $request->validate([
            'question'        => 'required|string|min:3|max:2000',
            'conversation_id' => 'nullable|integer',
            'images'          => 'nullable|array|max:3',
            'images.*'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'question.required' => 'Pertanyaan wajib diisi.',
            'images.max'        => 'Maksimal 3 screenshot percakapan WhatsApp.',
            'images.*.image'    => 'File lampiran harus berupa gambar.',
            'images.*.max'      => 'Ukuran setiap gambar maksimal 10 MB.',
        ]);

        try {
            $userId   = Auth::id();
            $question = $request->input('question');
            $convId   = $request->input('conversation_id');

            // Temukan atau buat sesi percakapan baru
            $conversation = null;
            if ($convId) {
                $conversation = DocumentationConversation::where('user_id', $userId)->find($convId);
            }

            if (!$conversation) {
                $conversation = DocumentationConversation::create([
                    'user_id' => $userId,
                    'title'   => Str::limit($question, 55),
                ]);
            } else {
                $conversation->touch(); // Perbarui updated_at agar muncul di paling atas riwayat
            }

            $storedPaths = [];
            $tmpPaths    = [];

            if ($request->hasFile('images')) {
                foreach (array_slice($request->file('images'), 0, 3) as $file) {
                    $storedPaths[] = $file->store('documentation/' . $userId, 'private');
                    $tmpPaths[]    = $file->getRealPath();
                }
            }

            // Panggil AI service dengan pertanyaan dan path gambar sementara
            $result = $this->chat->chat($question, $tmpPaths);

            // Simpan riwayat percakapan ke database
            $chatRecord = DocumentationChat::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $userId,
                'question'        => $question,
                'answer'          => $result['answer'],
                'citations'       => $result['citations'],
                'images'          => !empty($storedPaths) ? $storedPaths : null,
            ]);

            // Bangun URL gambar untuk respons frontend
            $imageUrls = [];
            if (!empty($chatRecord->images)) {
                foreach (array_keys($chatRecord->images) as $idx) {
                    $imageUrls[] = route('documentation.image', ['chat' => $chatRecord->id, 'index' => $idx]);
                }
            }

            return response()->json([
                'success'            => true,
                'conversation_id'    => $conversation->id,
                'conversation_title' => $conversation->title,
                'id'                 => $chatRecord->id,
                'question'           => $chatRecord->question,
                'answer'             => $chatRecord->answer,
                'citations'          => $chatRecord->citations,
                'images'             => $imageUrls,
                'created_at'         => $chatRecord->created_at->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ambil daftar sesi percakapan user untuk modal riwayat chat.
     */
    public function getConversations()
    {
        $conversations = DocumentationConversation::where('user_id', Auth::id())
            ->withCount('messages')
            ->latest('updated_at')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'title'          => $c->title,
                'messages_count' => $c->messages_count,
                'updated_at'     => $c->updated_at->toISOString(),
            ]);

        return response()->json(['success' => true, 'conversations' => $conversations]);
    }

    /**
     * Ambil rangkaian pesan dalam satu sesi percakapan.
     */
    public function getConversation(int $id)
    {
        $conversation = DocumentationConversation::where('user_id', Auth::id())
            ->with(['messages' => fn($q) => $q->orderBy('created_at', 'asc')])
            ->findOrFail($id);

        $messages = $conversation->messages->map(function ($m) {
            $imageUrls = [];
            if (!empty($m->images)) {
                foreach (array_keys($m->images) as $idx) {
                    $imageUrls[] = route('documentation.image', ['chat' => $m->id, 'index' => $idx]);
                }
            }
            return [
                'id'         => $m->id,
                'question'   => $m->question,
                'answer'     => $m->answer,
                'citations'  => $m->citations ?? [],
                'images'     => $imageUrls,
                'created_at' => $m->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success'      => true,
            'conversation' => [
                'id'         => $conversation->id,
                'title'      => $conversation->title,
                'created_at' => $conversation->created_at->toISOString(),
                'messages'   => $messages,
            ],
        ]);
    }

    /**
     * Sajikan gambar lampiran percakapan secara aman untuk pemilik chat.
     */
    public function image(DocumentationChat $chat, int $index = 0)
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $paths = (array) ($chat->images ?? []);

        if (empty($paths) || !isset($paths[$index])) {
            abort(404);
        }

        $path = $paths[$index];
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk('private')->mimeType($path);
        return response(Storage::disk('private')->get($path), 200)
            ->header('Content-Type', $mime)
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Hapus satu sesi percakapan beserta seluruh pesan dan file gambarnya.
     */
    public function destroyConversation(int $id)
    {
        $conv = DocumentationConversation::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if ($conv) {
            foreach ($conv->messages as $chat) {
                if (!empty($chat->images)) {
                    foreach ($chat->images as $path) {
                        if (Storage::disk('private')->exists($path)) {
                            Storage::disk('private')->delete($path);
                        }
                    }
                }
            }
            $conv->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Hapus seluruh sesi percakapan user.
     */
    public function clearAllConversations()
    {
        $convs = DocumentationConversation::where('user_id', Auth::id())->get();

        foreach ($convs as $conv) {
            foreach ($conv->messages as $chat) {
                if (!empty($chat->images)) {
                    foreach ($chat->images as $path) {
                        if (Storage::disk('private')->exists($path)) {
                            Storage::disk('private')->delete($path);
                        }
                    }
                }
            }
            $conv->delete();
        }

        return response()->json(['success' => true]);
    }
}
