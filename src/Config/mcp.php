<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP API Authentication
    |--------------------------------------------------------------------------
    |
    | When enabled, all MCP HTTP endpoints (SSE) require a valid Bearer token.
    | Default is true for production security.
    |
    */
    'api_auth' => env('MCP_API_AUTH', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Define the maximum number of requests allowed per minute per tool.
    |
    */
    'rate_limit' => env('MCP_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Remote Developer Tools
    |--------------------------------------------------------------------------
    |
    | Developer tools and dynamic skills (file writes, commands, SQL, logs) are
    | always available over stdio. Enable this to serve them over HTTP too.
    |
    */
    'remote_developer_tools' => env('MCP_REMOTE_DEVELOPER_TOOLS', false),

    /*
    |--------------------------------------------------------------------------
    | Path Restriction
    |--------------------------------------------------------------------------
    |
    | The file manager will only allow operations within these base paths.
    | Any attempt to access paths outside these will be rejected.
    |
    */
    'allowed_paths' => [
        base_path(),
        sys_get_temp_dir(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable audit logging for destructive tool operations.
    |
    */
    'audit_logging' => env('MCP_AUDIT_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Skills Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the dynamic skills system that loads SKILL.md files
    | from the filesystem and registers them as MCP tools.
    |
    */
    'skills_path' => env('MCP_SKILLS_PATH', base_path('.ai/skills')),

    'enable_cache' => env('MCP_ENABLE_CACHE', true),

    'cache_key' => 'mcp.skills',

    'cache_ttl' => env('MCP_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Media Validation
    |--------------------------------------------------------------------------
    |
    | Define allowed file extensions and MIME types for media uploads.
    |
    */
    'media' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'csv', 'xlsx'],
        'allowed_mimes' => [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'application/pdf', 'text/csv', 'text/plain', 'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Registration
    |--------------------------------------------------------------------------
    |
    | Caps applied to the dynamic client registration endpoint (oauth/register):
    | the request body size in bytes, the number and length of redirect URIs,
    | and the registrations allowed per minute from a single IP address.
    |
    */
    'oauth' => [
        'registration' => [
            'max_body_bytes' => env('MCP_OAUTH_REGISTRATION_MAX_BODY_BYTES', 16384),
            'max_redirect_uris' => env('MCP_OAUTH_REGISTRATION_MAX_REDIRECT_URIS', 10),
            'max_redirect_uri_length' => env('MCP_OAUTH_REGISTRATION_MAX_REDIRECT_URI_LENGTH', 2048),
            'rate_limit' => env('MCP_OAUTH_REGISTRATION_RATE_LIMIT', 10),
        ],
    ],
];
