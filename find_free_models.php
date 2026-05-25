<?php
$json = file_get_contents("https://openrouter.ai/api/v1/models");
$data = json_decode($json, true);
$free_models = [];
foreach ($data['data'] as $model) {
    if (strpos($model['id'], ':free') !== false || strpos($model['id'], '/free') !== false || ($model['pricing']['prompt'] === "0" && $model['pricing']['completion'] === "0")) {
        $free_models[] = $model['id'];
    }
}
echo "Free models:\n" . implode("\n", array_slice($free_models, 0, 10)) . "\n";
?>
