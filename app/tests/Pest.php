<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Unit:    regras do domínio (PHP puro, sem framework)
| Feature: API, sincronização, policies e telas (Laravel + SQLite em memória)
| Arch:    regras de arquitetura entre as camadas
*/

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Arch');
