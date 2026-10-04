<?php

namespace App\Http\Controllers;

use App\Models\DocumentationChat;
use App\Services\DocumentationChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentationController extends Controller
{
    public function __construct(private DocumentationChatService $chat) {}

    public function index()
    {
        $chats = DocumentationChat::where('user_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('documentation.index', compact('chats'));
    }

    public function chat(Request $request)
    {
        $request->validate([
            'question' => 'required|string|min:3|max:2000',
            'images'   => 'nullable|array|max:3',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'question.required' => 'Pertanyaan wajib diisi.',
            'images.max'        => 'Maksimal 3 screenshot percakapan WhatsApp.',
            'images.*.image'    => 'File lampiran harus berupa gambar.',
            'images.*.max'      => 'Ukuran setiap gambar maksimal 10 MB.',
        ]);

        try {
            $question = $request->input('question');
            $storedPaths = [];
            $tmpPaths    = [];

            if ($request->hasFile('images')) {
                foreach (array_slice($request->file('images'), 0, 3) as $file) {
                    $storedPaths[] = $file->store('documentation/' . Auth::id(), 'private');
                    $tmpPaths[]    = $file->getRealPath();
                }
            }

            // Panggil AI service dengan pertanyaan dan path gambar sementara
            $result = $this->chat->chat($question, $tmpPaths);

            // Simpan riwayat percakapan ke database
            $chatRecord = DocumentationChat::create([
                'user_id'   => Auth::id(),
                'question'  => $question,
                'answer'    => $result['answer'],
                'citations' => $result['citations'],
                'images'    => !empty($storedPaths) ? $storedPaths : null,
            ]);

            // Bangun URL gambar untuk respons frontend
            $imageUrls = [];
            if (!empty($chatRecord->images)) {
                foreach (array_keys($chatRecord->images) as $idx) {
                    $imageUrls[] = route('documentation.image', ['chat' => $chatRecord->id, 'index' => $idx]);
                }
            }

            return response()->json([
                'success'    => true,
                'id'         => $chatRecord->id,
                'question'   => $chatRecord->question,
                'answer'     => $chatRecord->answer,
                'citations'  => $chatRecord->citations,
                'images'     => $imageUrls,
                'created_at' => $chatRecord->created_at->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
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

    public function destroy(int $id)
    {
        $chat = DocumentationChat::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if ($chat) {
            // Hapus file gambar jika ada
            if (!empty($chat->images)) {
                foreach ($chat->images as $path) {
                    if (Storage::disk('private')->exists($path)) {
                        Storage::disk('private')->delete($path);
                    }
                }
            }
            $chat->delete();
        }

        return response()->json(['success' => true]);
    }

    public function clearAll()
    {
        $chats = DocumentationChat::where('user_id', Auth::id())->get();

        foreach ($chats as $chat) {
            if (!empty($chat->images)) {
                foreach ($chat->images as $path) {
                    if (Storage::disk('private')->exists($path)) {
                        Storage::disk('private')->delete($path);
                    }
                }
            }
            $chat->delete();
        }

        return response()->json(['success' => true]);
    }
}
