<?php

use Gametech\LineOA\Http\Controllers\Admin\ChatController;
use Gametech\LineOA\Http\Controllers\Admin\LineAccountController;
use Gametech\LineOA\Http\Controllers\Admin\LineQuickReplyController;
use Gametech\LineOA\Http\Controllers\Admin\LineTemplateController;
use Gametech\LineOA\Http\Controllers\Admin\TopupController;
use Illuminate\Support\Facades\Route;

// admin.xxx.com
Route::domain(
    config('app.admin_url').'.'.(
        is_null(config('app.admin_domain_url'))
            ? config('app.domain_url')
            : config('app.admin_domain_url')
    )
)->group(function () {

    Route::group(['middleware' => ['web', 'admin', 'auth', '2fa']], function () {
        Route::prefix('line-oa')
            ->as('admin.line-oa.') // 👈 ชื่อ route ทั้งกลุ่มขึ้นต้น admin.
            ->group(function () {

                Route::get('chat', [ChatController::class, 'page'])->name('chat');

                Route::get('conversations', [ChatController::class, 'index'])
                    ->name('conversations.index');
                // => admin.line-oa.conversations.index

                Route::get('conversations/{conversation}', [ChatController::class, 'show'])
                    ->name('conversations.show');
                // => admin.line-oa.conversations.show

                Route::post('conversations/{conversation}/reply', [ChatController::class, 'reply'])
                    ->name('conversations.reply');
                // => admin.line-oa.conversations.reply

                Route::post('conversations/{conversation}/reply-image', [ChatController::class, 'replyImage'])
                    ->name('conversations.reply_image');

                Route::get('messages/{message}/content', [ChatController::class, 'content',
                ])->name('messages.content');

                Route::get('conversations/{conversation}/quick-replies', [ChatController::class, 'quickReplies'])
                    ->name('conversations.quick-replies');

                Route::post('conversations/{conversation}/reply-template', [ChatController::class, 'replyTemplate'])
                    ->name('conversations.reply-template');

                Route::post('conversations/{conversation}/reply-sticker', [ChatController::class, 'replySticker'])
                    ->name('conversations.reply-sticker');

                Route::post('conversations/{conversation}/pin', [ChatController::class, 'pinConversation']);

                Route::post('conversations/{conversation}/unpin', [ChatController::class, 'unpinConversation']);

                Route::post('messages/{message}/pin', [ChatController::class, 'pinMessage']);

                Route::post('messages/{message}/unpin', [ChatController::class, 'unpinMessage']);

                Route::get('members/find', [ChatController::class, 'findMember'])->name('members.find');

                Route::get('register/load-bank', [ChatController::class, 'loadBank'])->name('register.load-bank');

                Route::post('register/check-bank', [ChatController::class, 'checkBank'])->name('register.check-bank');

                Route::post('register/check-phone', [ChatController::class, 'checkPhone'])->name('register.check-phone');

                Route::post('register/check-user', [ChatController::class, 'checkUser'])->name('register.check-user');


                Route::post('register/member', [ChatController::class, 'registerMember'])->name('register.member');

                Route::post('contacts/{contact}/attach-member', [ChatController::class, 'attachMember'])
                    ->name('contacts.attach-member');

                Route::post('conversations/{conversation}/accept', [ChatController::class, 'accept'])
                    ->name('conversations.accept');
                // => admin.line-oa.conversations.accept

                Route::post('conversations/{conversation}/lock', [ChatController::class, 'lock'])
                    ->name('conversations.lock');
                // => admin.line-oa.conversations.lock

                Route::post('conversations/{conversation}/unlock', [ChatController::class, 'unlock'])
                    ->name('conversations.unlock');

                Route::post('conversations/{conversation}/close', [ChatController::class, 'close'])
                    ->name('conversations.close');

                Route::post('conversations/{conversation}/open', [ChatController::class, 'open'])
                    ->name('conversations.open');

                Route::post('conversations/{conversation}/cancel-register', [ChatController::class, 'cancelRegister'])
                    ->name('conversations.cancel-register');

                Route::get('get-balance', [ChatController::class, 'getBalance'])
                    ->name('getbalance');

                Route::get('assignees', [ChatController::class, 'assignees'])->name('assignees');

                // กำหนดผู้รับผิดชอบให้ห้อง
                Route::post('conversations/{conversation}/assign', [ChatController::class, 'assign'])->name('conversations.assign');

                Route::get('conversations/{conversation}/notes', [ChatController::class, 'listNotes'])->name('conversations.list-notes');
                Route::post('conversations/{conversation}/notes', [ChatController::class, 'storeNote'])->name('conversations.store-notes');
                Route::patch('conversations/{conversation}/notes/{note}', [ChatController::class, 'updateNote'])->name('conversations.notes.update');
                Route::delete('conversations/{conversation}/notes/{note}', [ChatController::class, 'destroyNote'])->name('conversations.notes.destroy');

                // ✅ typing indicator (global)
                Route::post('typing', [ChatController::class, 'typing'])->name('typing');

            });

        Route::prefix('line_account')->group(function () {
            Route::get('/', [LineAccountController::class, 'index'])->defaults('_config', [
                'view' => 'admin::module.line_account.index',
            ])->name('admin.line_account.index');
            Route::post('create', [LineAccountController::class, 'create'])->name('admin.line_account.create');
            Route::post('loaddata', [LineAccountController::class, 'loadData'])->name('admin.line_account.loaddata');
            Route::post('edit', [LineAccountController::class, 'edit'])->name('admin.line_account.edit');
            Route::post('update/{id?}', [LineAccountController::class, 'update'])->name('admin.line_account.update');
            Route::post('delete', [LineAccountController::class, 'destroy'])->name('admin.line_account.delete');
        });

        Route::prefix('line_template')->group(function () {
            Route::get('/', [LineTemplateController::class, 'index'])->defaults('_config', [
                'view' => 'admin::module.line_template.index',
            ])->name('admin.line_template.index');
            Route::post('create', [LineTemplateController::class, 'create'])->name('admin.line_template.create');
            Route::post('loaddata', [LineTemplateController::class, 'loadData'])->name('admin.line_template.loaddata');
            Route::post('edit', [LineTemplateController::class, 'edit'])->name('admin.line_template.edit');
            Route::post('update/{id?}', [LineTemplateController::class, 'update'])->name('admin.line_template.update');
            Route::post('delete', [LineTemplateController::class, 'destroy'])->name('admin.line_template.delete');
            Route::post('upload-image', [LineTemplateController::class, 'uploadImage'])->name('admin.line_template.upload_image');
        });

        Route::prefix('line_quick_reply')->group(function () {
            Route::get('/', [LineQuickReplyController::class, 'index'])->defaults('_config', [
                'view' => 'admin::module.line_quick_reply.index',
            ])->name('admin.line_quick_reply.index');
            Route::post('create', [LineQuickReplyController::class, 'create'])->name('admin.line_quick_reply.create');
            Route::post('loaddata', [LineQuickReplyController::class, 'loadData'])->name('admin.line_quick_reply.loaddata');
            Route::post('edit', [LineQuickReplyController::class, 'edit'])->name('admin.line_quick_reply.edit');
            Route::post('update/{id?}', [LineQuickReplyController::class, 'update'])->name('admin.line_quick_reply.update');
            Route::post('delete', [LineQuickReplyController::class, 'destroy'])->name('admin.line_quick_reply.delete');
        });

        Route::prefix('line_topup')->group(function () {
            Route::get('/', [TopupController::class, 'index'])->defaults('_config', [
                'view' => 'admin::module.line_topup.index',
            ])->name('admin.line_topup.index');
            Route::post('loaddata', [TopupController::class, 'loadData'])->name('admin.line_topup.loaddata');
            Route::post('update/{id?}', [TopupController::class, 'update'])->name('admin.line_topup.update');
            Route::post('delete', [TopupController::class, 'destroy'])->name('admin.line_topup.delete');
        });

    });

});
