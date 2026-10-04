<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documentation_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        Schema::table('documentation_chats', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('user_id')->constrained('documentation_conversations')->cascadeOnDelete();
        });

        // Migrasikan chat lama (jika ada) ke dalam sesi percakapan awal
        $existingChats = \DB::table('documentation_chats')->whereNull('conversation_id')->orderBy('id')->get();
        if ($existingChats->isNotEmpty()) {
            $byUser = $existingChats->groupBy('user_id');
            foreach ($byUser as $userId => $chats) {
                $firstChat = $chats->first();
                $title = \Illuminate\Support\Str::limit($firstChat->question, 60);
                $convId = \DB::table('documentation_conversations')->insertGetId([
                    'user_id'    => $userId,
                    'title'      => $title,
                    'created_at' => $firstChat->created_at ?? now(),
                    'updated_at' => $chats->last()->created_at ?? now(),
                ]);

                \DB::table('documentation_chats')->whereIn('id', $chats->pluck('id'))->update([
                    'conversation_id' => $convId,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentation_chats', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });

        Schema::dropIfExists('documentation_conversations');
    }
};
