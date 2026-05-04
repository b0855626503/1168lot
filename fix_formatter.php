<?php
$content = file_get_contents('packages/Gametech/Lotto/src/Support/LottoMarketDisplayFormatter.php');

$old1 = <<<OLD
    public function formatStatusMessage(
        string \$marketName,
        string \$drawDate,
        string \$eventSuffix,
        ?string \$resultMode = null,
        ?int \$roundNo = null
    ): string {
        return \$this->formatDrawSubject(\$marketName, \$drawDate, \$resultMode, \$roundNo).' '.\$eventSuffix;
    }

    /**
     * Build the subject portion "{name} [รอบที่ X] งวดวันที่ {date}" without a status suffix.
     */
    public function formatDrawSubject(
        string \$marketName,
        string \$drawDate,
        ?string \$resultMode = null,
        ?int \$roundNo = null
    ): string {
        \$name = trim(\$marketName) !== '' ? trim(\$marketName) : '-';
        \$dateLabel = \$this->formatDrawDate(\$drawDate);

        if (\$this->shouldShowRoundBadge(\$resultMode, \$roundNo)) {
            return "{\$name} รอบที่ {\$roundNo} งวดวันที่ {\$dateLabel}";
        }

        return "{\$name} งวดวันที่ {\$dateLabel}";
    }
OLD;

$new1 = <<<NEW
    public function formatStatusMessage(
        string \$marketName,
        string \$drawDate,
        string \$eventSuffix,
        ?string \$resultMode = null,
        ?int \$roundNo = null,
        bool \$fullDate = false
    ): string {
        return \$this->formatDrawSubject(\$marketName, \$drawDate, \$resultMode, \$roundNo, \$fullDate).' '.\$eventSuffix;
    }

    /**
     * Build the subject portion "{name} [รอบที่ X] งวดวันที่ {date}" without a status suffix.
     */
    public function formatDrawSubject(
        string \$marketName,
        string \$drawDate,
        ?string \$resultMode = null,
        ?int \$roundNo = null,
        bool \$fullDate = false
    ): string {
        \$name = trim(\$marketName) !== '' ? trim(\$marketName) : '-';
        \$dateLabel = \$fullDate ? \$drawDate : \$this->formatDrawDate(\$drawDate);

        if (\$this->shouldShowRoundBadge(\$resultMode, \$roundNo)) {
            return "{\$name} รอบที่ {\$roundNo} งวดวันที่ {\$dateLabel}";
        }

        return "{\$name} งวดวันที่ {\$dateLabel}";
    }
NEW;

$content = str_replace($old1, $new1, $content);
file_put_contents('packages/Gametech/Lotto/src/Support/LottoMarketDisplayFormatter.php', $content);
