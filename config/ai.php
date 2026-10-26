<?php

/*
|--------------------------------------------------------------------------
| AI layer (16)
|--------------------------------------------------------------------------
|
| The providers are those of the laravel/ai package; the tasks, prompts and
| prices below are ours. Features never call a provider directly: they run a
| task through App\Services\Ai\AiGateway. A model is changed here, with a
| deploy (16 · 16.1). API keys live only in .env.
|
*/

return [

    'default' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
    'default_for_images' => 'gemini',
    'default_for_audio' => 'openai',
    'default_for_transcription' => 'openai',
    'default_for_embeddings' => 'openai',
    'default_for_reranking' => 'cohere',
    'default_for_classification' => 'typesafe',

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
            'individually' => true,
        ],
    ],

    /*
    | Data stays in the EU (16 · 16.1): Claude through Amazon Bedrock in an EU
    | region, OpenAI with EU data residency (https://eu.api.openai.com/v1).
    */
    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],

        'bedrock' => [
            'driver' => 'bedrock',
            'region' => env('AWS_BEDROCK_REGION', 'eu-central-1'),
            'key' => env('AWS_BEARER_TOKEN_BEDROCK'),
            'access_key_id' => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            'session_token' => env('AWS_SESSION_TOKEN'),
            'use_default_credential_provider' => env('AWS_USE_DEFAULT_CREDENTIALS', true),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://eu.api.openai.com/v1'),
            'store' => false,
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/'),
        ],
    ],

    /*
    | Where the versioned prompts live: {prompts_path}/{prompt}/v{n}.md (16 · 16.2).
    */
    'prompts_path' => resource_path('prompts'),

    /*
    | Tasks of the MVP (16 · 16.1). A task without a prompt version cannot run
    | yet; its ticket adds the prompt. The models are decided with the 12.7 PoC
    | (16, open question 1). max_tokens is a ceiling, not a cost: Gemini counts
    | its thinking into it, and an answer cut off there fails the call.
    */
    'tasks' => [
        'mirror_detection' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 16384,
            'temperature' => 0.2,
            'timeout' => 120,
            'prompt' => 'ertektukor-detektalas',
            'prompt_version' => 2,
        ],
        'mirror_matching' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 16384,
            'temperature' => 0.0,
            'timeout' => 120,
            'prompt' => 'ertektukor-illesztes',
            'prompt_version' => 3,
        ],
        'mirror_scoring' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 16384,
            'temperature' => 0.0,
            'timeout' => 120,
            'prompt' => 'ertektukor-pontozas',
            'prompt_version' => 2,
        ],
        // ET4: the mégis Coach puts each value of a mirror into the user's own words (NM-06).
        'mirror_deepening' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 4096,
            'temperature' => 0.4,
            'timeout' => 120,
            'prompt' => 'ertektukor-elmelyules',
            'prompt_version' => 1,
        ],
        'calendar_naming' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_SMALL_MODEL', 'claude-haiku-5-5'),
            'max_tokens' => 1024,
            'temperature' => 0.2,
            'timeout' => 60,
            'prompt' => 'naptar-elnevezes',
            'prompt_version' => 1,
        ],
        'conversation_summary' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 2048,
            'temperature' => 0.2,
            'timeout' => 90,
            'prompt' => 'beszelgetes-kivonat',
            'prompt_version' => 1,
        ],
        // Several processes at once from a written or spoken text (11 · 11.2, 11.6).
        'process_parsing' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 8192,
            'temperature' => 0.2,
            'timeout' => 90,
            'prompt' => 'folyamat-szovegbol',
            'prompt_version' => 1,
        ],
        'occasion_analysis' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 4096,
            'temperature' => 0.0,
            'timeout' => 120,
            'prompt' => 'alkalom-elemzes',
            'prompt_version' => null,
        ],
        'transcription' => [
            'provider' => env('AI_TRANSCRIPTION_PROVIDER', 'openai'),
            'model' => env('AI_TRANSCRIPTION_MODEL'),
            'max_tokens' => null,
            'temperature' => null,
            'timeout' => 300,
            'prompt' => null,
            'prompt_version' => null,
        ],
        'diagnosis_scoring' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 8192,
            'temperature' => 0.0,
            'timeout' => 180,
            'prompt' => 'diagnozis-pontozas',
            'prompt_version' => null,
        ],
        'diagnosis_claims' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 8192,
            'temperature' => 0.3,
            'timeout' => 180,
            'prompt' => 'diagnozis-allitasok',
            'prompt_version' => 1,
        ],
        // Képességmérés (NM-12): A traces per chunk, B patterns per capability.
        'capability_detection' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 16384,
            'temperature' => 0.0,
            'timeout' => 180,
            'prompt' => 'kepesseg-nyomkereses',
            'prompt_version' => 1,
        ],
        'capability_matching' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 8192,
            'temperature' => 0.0,
            'timeout' => 180,
            'prompt' => 'kepesseg-mintaillesztes',
            'prompt_version' => 1,
        ],
        'reading_texts' => [
            'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
            'model' => env('AI_DEFAULT_MODEL', 'claude-sonnet-5-5'),
            'max_tokens' => 4096,
            'temperature' => 0.4,
            'timeout' => 180,
            'prompt' => 'olvasat-szovegek',
            'prompt_version' => 2,
        ],
    ],

    /*
    | Unit prices in USD (16 · 16.4): per million tokens, and per minute of
    | audio. The cost is calculated when the call is made and saved with it;
    | later price changes do not touch old calls. A model without a price is
    | flagged on Rendszer → AI and by the setup check.
    |
    | megis:prices:sync refreshes the prices of the models in use daily from
    | the LiteLLM price list into the ai_model_prices table. A price here
    | overrides that one, e.g. for a model the list does not know.
    |
    | 'model-id' => ['input' => 0.0, 'output' => 0.0, 'cache_read' => 0.0, 'cache_write' => 0.0, 'audio_minute' => 0.0],
    */
    'prices' => [
        //
    ],

    'price_source' => env('AI_PRICE_SOURCE_URL') ?: 'https://raw.githubusercontent.com/BerriAI/litellm/main/model_prices_and_context_window.json',

];
