Translate only the supplied keyed text segments into the target language.

SOURCE_LANGUAGE: {{ source_language }}
TARGET_LANGUAGE: {{ target_language }}
TARGET_DIRECTION: {{ target_direction }}
CONTEXT_JSON: {{ context }}
USER_CONTENT_JSON: {{ segments }}

Return every input segment exactly once with the same id. Translate text only. Never translate HTML classes, URLs, technical identifiers, or CSS values.
