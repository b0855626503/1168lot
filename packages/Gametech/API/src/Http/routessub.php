<?php

Route::prefix('5ggames')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('759gaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('pgsoft')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
        Route::post('settleBets', 'NewCommonFlowController@settleBets');
    });
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('pgslot')->group(function () {
    Route::post('checkBalance', 'PgsoftController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('settleBets', 'PgsoftController@transferOut');
    });
});

Route::prefix('ace333')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('advantplay')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('afb1188livecasino')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('afb1188sportbook')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('afb1188')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('allbet')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ambgaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ambpoker')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ambsportbook')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ameba')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('askmeplay')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ambslot')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('askmeslot')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('asiagamingag')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('askmebet')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
        Route::post('settleBets', 'NewCommonFlowController@settleBets');
    });

    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('biggaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('cockfight')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('cq9')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('creativegaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('dragongaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('dreamgaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('evoplay')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('fachai')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('giocoplus')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('hacksaw')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('i8')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('jili')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('joker')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
        Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('joker123')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
        Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('kingmaker')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('lalika')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('live22')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('mannaplay')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('microgaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('microgaminglivecasino')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('mikiworlds')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('cq9casino')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('netent')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('nextspin')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ninesgame')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('nolimitcity')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('octoplay')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('playngo')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('pragmaticplay')->group(function () {
    Route::post('checkBalance', 'PragmaticPlayController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('settleBets', 'PragmaticPlayController@transferOut');
    });
});

Route::prefix('pragmaticplaylivecasino')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('pragmaticplayslot')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('prettygaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('redtiger')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('relaxgaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('rich88')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('royalslotgaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('sagaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('sexygaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('sexyslot')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('simpleplay')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('slotxo')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
        Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('spadegaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('upgslot')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('virtualsportbook')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('wecasino')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('wmcasino')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('wmslot')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('yggdrasil')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('yggdrasilgaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ygr')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('funkygames')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('sbobet')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('huaydragon')->group(function () {
    Route::post('balance', 'HuayDragonController@getBalance');
    Route::post('bet', 'HuayDragonController@transferOut');
    Route::post('payout', 'HuayDragonController@transferIn');
    Route::post('void', 'HuayDragonController@unsettleBets');
    Route::post('cancel_number', 'HuayDragonController@cancelNumber');
    Route::post('cancel', 'HuayDragonController@cancelBets');
    Route::post('winRewards', 'HuayDragonController@winRewards');
    Route::post('transaction', 'HuayDragonController@transaction');
    Route::post('voidBets', 'HuayDragonController@voidBets');
});

Route::prefix('kagaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('sabasports')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('bolebit')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('betgamestv')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('wazdan')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('goldy')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('booming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('booongo')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('funtagaming')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('parlaybay')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('yeebet')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('habanero')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('bigpot')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('keno')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ksgame')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('aog')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('fbsport')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('umbet')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

Route::prefix('ambsuperapi')->group(function () {
    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
    Route::middleware('ensure.in.game')->group(function () {
        Route::post('placeBets', 'NewCommonFlowController@placeBets');
    });
    Route::post('settleBets', 'NewCommonFlowController@settleBets');
    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
    Route::post('winRewards', 'NewCommonFlowController@winRewards');
    Route::post('transaction', 'NewCommonFlowController@transaction');
    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
    Route::post('rollback', 'NewCommonFlowController@rollback');
    Route::post('placeTips', 'NewCommonFlowController@placeTips');
    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
});

// Route::prefix('ambsuperapi')->group(function () {
//    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
//
//    // หน่วงเวลาเฉพาะเส้นนี้ เมื่อเปิด config('api.test.allow_delay')
//    Route::post('placeBets', 'NewCommonFlowController@placeBets')
//        ->middleware([
//            'ensure.in.game',
//            ...(config('api.test.allow_delay') ? ['test.delay'] : []),
//        ]);
//
//    // เส้นอื่นไม่โดน delay
//    Route::post('settleBets',   'NewCommonFlowController@settleBets');
//    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
//    Route::post('adjustBets',   'NewCommonFlowController@adjustBets');
//    Route::post('adjustBalance','NewCommonFlowController@adjustBalance');
//    Route::post('cancelBets',   'NewCommonFlowController@cancelBets');
//    Route::post('winRewards',   'NewCommonFlowController@winRewards');
//    Route::post('transaction',  'NewCommonFlowController@transaction');
//    Route::post('voidSettled',  'NewCommonFlowController@voidSettled');
//    Route::post('rollback',     'NewCommonFlowController@rollback');
//    Route::post('placeTips',    'NewCommonFlowController@placeTips');
//    Route::post('cancelTips',   'NewCommonFlowController@cancelTips');
// });

// Route::prefix('pgapi')->group(function () {
//    Route::post('checkBalance', 'NewCommonFlowController@getBalance');
//    Route::middleware('ensure.in.game')->group(function () {
//        Route::post('placeBets', 'NewCommonFlowController@placeBets');
//    });
//    Route::post('settleBets', 'NewCommonFlowController@settleBets');
//    Route::post('unsettleBets', 'NewCommonFlowController@unsettleBets');
//    Route::post('adjustBets', 'NewCommonFlowController@adjustBets');
//    Route::post('adjustBalance', 'NewCommonFlowController@adjustBalance');
//    Route::post('cancelBets', 'NewCommonFlowController@cancelBets');
//    Route::post('winRewards', 'NewCommonFlowController@winRewards');
//    Route::post('transaction', 'NewCommonFlowController@transaction');
//    Route::post('voidSettled', 'NewCommonFlowController@voidSettled');
//    Route::post('rollback', 'NewCommonFlowController@rollback');
//    Route::post('placeTips', 'NewCommonFlowController@placeTips');
//    Route::post('cancelTips', 'NewCommonFlowController@cancelTips');
// });
//
//
// Route::prefix('apiredis')->group(function () {
//    Route::post('checkBalance', 'NewCommonFlowRedisController@getBalance');
//    Route::middleware('ensure.in.game')->group(function () {
//        Route::post('placeBets', 'NewCommonFlowRedisController@placeBets');
//    });
//    Route::post('settleBets', 'NewCommonFlowRedisController@settleBets');
//    Route::post('unsettleBets', 'NewCommonFlowRedisController@unsettleBets');
//    Route::post('adjustBets', 'NewCommonFlowRedisController@adjustBets');
//    Route::post('adjustBalance', 'NewCommonFlowRedisController@adjustBalance');
//    Route::post('cancelBets', 'NewCommonFlowRedisController@cancelBets');
//    Route::post('winRewards', 'NewCommonFlowRedisController@winRewards');
//    Route::post('transaction', 'NewCommonFlowRedisController@transaction');
//    Route::post('voidSettled', 'NewCommonFlowRedisController@voidSettled');
//    Route::post('rollback', 'NewCommonFlowRedisController@rollback');
//    Route::post('placeTips', 'NewCommonFlowRedisController@placeTips');
//    Route::post('cancelTips', 'NewCommonFlowRedisController@cancelTips');
// });
//
// Route::prefix('newcommon')->group(function () {
//    Route::post('checkBalance', 'NewCommonController@getBalance');
//    Route::middleware('ensure.in.game')->group(function () {
//        Route::post('placeBets', 'NewCommonController@placeBets');
//    });
//    Route::post('settleBets', 'NewCommonController@settleBets');
// //    Route::post('unsettleBets', 'NewCommonFlowRedisController@unsettleBets');
//    Route::post('adjustBets', 'NewCommonController@adjustBets');
//    Route::post('adjustBalance', 'NewCommonController@adjustBalance');
//    Route::post('cancelBets', 'NewCommonController@cancelBets');
//    Route::post('winRewards', 'NewCommonController@winRewards');
//    Route::post('transaction', 'NewCommonController@transaction');
//    Route::post('voidSettled', 'NewCommonController@voidSettled');
//    Route::post('rollback', 'NewCommonController@rollback');
//    Route::post('placeTips', 'NewCommonController@placeTips');
//    Route::post('cancelTips', 'NewCommonController@cancelTips');
// });
//
// Route::prefix('newcommonv2')->group(function () {
//    Route::post('checkBalance', 'NewCommonV2Controller@getBalance');
//    Route::middleware('ensure.in.game')->group(function () {
//        Route::post('placeBets', 'NewCommonV2Controller@placeBets');
//    });
//    Route::post('settleBets', 'NewCommonV2Controller@settleBets');
// //    Route::post('unsettleBets', 'NewCommonFlowRedisController@unsettleBets');
//    Route::post('adjustBets', 'NewCommonV2Controller@adjustBets');
//    Route::post('adjustBalance', 'NewCommonV2Controller@adjustBalance');
//    Route::post('cancelBets', 'NewCommonV2Controller@cancelBets');
//    Route::post('winRewards', 'NewCommonV2Controller@winRewards');
//    Route::post('transaction', 'NewCommonV2Controller@transaction');
//    Route::post('voidSettled', 'NewCommonV2Controller@voidSettled');
//    Route::post('rollback', 'NewCommonV2Controller@rollback');
//    Route::post('placeTips', 'NewCommonV2Controller@placeTips');
//    Route::post('cancelTips', 'NewCommonV2Controller@cancelTips');
// });
//
// Route::prefix('newcommonv1')->group(function () {
//    Route::post('checkBalance', 'NewCommonV0Controller@getBalance');
// //    Route::post('checkBalance', 'NewCommonV1Controller@getBalance');
//    Route::middleware('ensure.in.game')->group(function () {
//        Route::post('placeBets', 'NewCommonV1Controller@placeBets');
//    });
//    Route::post('settleBets', 'NewCommonV1Controller@settleBets');
// //    Route::post('unsettleBets', 'NewCommonFlowRedisController@unsettleBets');
//    Route::post('adjustBets', 'NewCommonV1Controller@adjustBets');
//    Route::post('adjustBalance', 'NewCommonV1Controller@adjustBalance');
//    Route::post('cancelBets', 'NewCommonV1Controller@cancelBets');
//    Route::post('winRewards', 'NewCommonV1Controller@winRewards');
//    Route::post('transaction', 'NewCommonV1Controller@transaction');
//    Route::post('voidSettled', 'NewCommonV1Controller@voidSettled');
//    Route::post('rollback', 'NewCommonV1Controller@rollback');
//    Route::post('placeTips', 'NewCommonV1Controller@placeTips');
//    Route::post('cancelTips', 'NewCommonV1Controller@cancelTips');
// });
//
// Route::prefix('newcommonv3')->group(function () {
//    Route::post('checkBalance', 'NewCommonV3Controller@getBalance');
//    Route::middleware('ensure.in.game')->group(function () {
//        Route::post('placeBets', 'NewCommonV3Controller@placeBets');
//    });
//    Route::post('settleBets', 'NewCommonV3Controller@settleBets');
// //    Route::post('unsettleBets', 'NewCommonFlowRedisController@unsettleBets');
//    Route::post('adjustBets', 'NewCommonV3Controller@adjustBets');
//    Route::post('adjustBalance', 'NewCommonV3Controller@adjustBalance');
//    Route::post('cancelBets', 'NewCommonV3Controller@cancelBets');
//    Route::post('winRewards', 'NewCommonV3Controller@winRewards');
//    Route::post('transaction', 'NewCommonV3Controller@transaction');
//    Route::post('voidSettled', 'NewCommonV3Controller@voidSettled');
//    Route::post('rollback', 'NewCommonV3Controller@rollback');
//    Route::post('placeTips', 'NewCommonV3Controller@placeTips');
//    Route::post('cancelTips', 'NewCommonV3Controller@cancelTips');
// });
//

Route::prefix('rb7lotto')->group(function () {
    Route::post('getbalance', 'RbLottoController@getBalance');

    Route::post('bet', 'RbLottoController@placeBet');
    Route::post('settle', 'RbLottoController@settleBet');
    Route::post('rollback-settle', 'RbLottoController@rollBack');
    Route::post('cancelbet', 'RbLottoController@cancelBets');
});
