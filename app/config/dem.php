<?php

return [
    // Critérios de aprovação (média mínima e frequência mínima — art. 24, VI, da LDB)
    'media_minima' => (float) env('DEM_MEDIA_MINIMA', 6.0),
    'frequencia_minima' => (float) env('DEM_FREQUENCIA_MINIMA', 75.0),

    // Token Sanctum usado pela PWA do professor (operação offline prolongada)
    'pwa_token_dias' => (int) env('DEM_PWA_TOKEN_DIAS', 30),

    // Limite de operações por lote de sincronização
    'sync_max_operacoes' => (int) env('DEM_SYNC_MAX_OPERACOES', 200),

    // Dias de retenção do registro de operações de sincronização
    'sync_retencao_dias' => (int) env('DEM_SYNC_RETENCAO_DIAS', 180),

    // Versão implantada (tag da imagem), exibida no healthcheck
    'versao' => env('APP_VERSION', 'dev'),
];
