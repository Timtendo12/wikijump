<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Wikidot\Utils\AjaxModuleWikiFlowController;

require ('../php/setup.php');

// to avoid caching
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Content-Type: text/plain; charset=\"UTF-8\"");

try {
    $controller = new AjaxModuleWikiFlowController();
    $controller->process();
} catch (Exception $e) {
    echo "A nasty error has occurred. If the problem repeats, please fill (if possible) a bug report.";
    echo "<br/><br/>";
    echo $e;
    Log::error("[OZONE] Exception caught:\n\n" . $e->__toString());
}
