<?php
require_once 'vendor/autoload.php';

try {
    $mpdf = new \Mpdf\Mpdf();
    echo "mPDF carregado com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
