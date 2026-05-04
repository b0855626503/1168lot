<?php
$content = file_get_contents('tests/Feature/Lotto/LottoWinningSettlementMaterializationTest.php');

$old_drop = "Schema::dropIfExists('members');";
$new_drop = "Schema::dropIfExists('members');\n        Schema::dropIfExists('yeekee_rounds');";
$content = str_replace($old_drop, $new_drop, $content);

$old_create = "Schema::create('members', function (Blueprint \$table): void {";
$new_create = "Schema::create('yeekee_rounds', function (Blueprint \$table): void {\n            \$table->bigIncrements('id');\n            \$table->unsignedBigInteger('lotto_draw_id');\n            \$table->integer('round_no');\n        });\n\n        Schema::create('members', function (Blueprint \$table): void {";
$content = str_replace($old_create, $new_create, $content);

file_put_contents('tests/Feature/Lotto/LottoWinningSettlementMaterializationTest.php', $content);
