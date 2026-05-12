/**
 * SparkPlus AI Provider Classes
 *
 * Each provider class mirrors the server-side PHP provider logic.
 * All API calls are made directly from the browser (no PHP proxying).
 *
 * Providers:
 *   - SparkPlusOpenAIProvider   (text + image)
 *   - SparkPlusAnthropicProvider (text only)
 *   - SparkPlusGeminiProvider   (text + image)
 *   - SparkPlusProviderFactory  (static factory / model→provider lookup)
 */

/* global window */

// ── OpenAI ──────────────────────────────────────────────────────────────────

class SparkPlusOpenAIProvider {
    constructor(apiKey) {
        this.apiKey    = apiKey;
        this._textUrl  = 'https://api.openai.com/v1/chat/completions';
        this._imageUrl = 'https://api.openai.com/v1/images/generations';
    }

    _headers() {
        return {
            'Authorization': 'Bearer ' + this.apiKey,
            'Content-Type':  'application/json',
        };
    }

    /**
     * Build fetch() params for a text generation request.
     *
     * @param {string} model  Model ID (e.g. 'gpt-5.5').
     * @param {string} prompt Full prompt string.
     * @returns {{ api_url, headers, request_body }}
     */
    buildTextRequest(model, prompt) {
        // Models that use the /v1/responses endpoint.
        const responsesModels = ['gpt-5.5-pro'];
        if (responsesModels.includes(model)) {
            return {
                api_url:      'https://api.openai.com/v1/responses',
                headers:      this._headers(),
                request_body: {
                    model,
                    input: prompt,
                    text:  { format: { type: 'json_object' } },
                },
            };
        }

        const body = {
            model:                model,
            messages:             [{ role: 'user', content: prompt }],
            max_completion_tokens: 16000,
            response_format:      { type: 'json_object' },
        };
        // Models that only support default temperature (1) — omit the param entirely.
        const noTempModels = ['gpt-5-mini', 'gpt-5-nano', 'gpt-5.5'];
        if (!noTempModels.includes(model)) {
            body.temperature = 0.7;
        }
        return { api_url: this._textUrl, headers: this._headers(), request_body: body };
    }

    /**
     * Build fetch() params for an image generation request.
     *
     * @param {string} model  Model ID.
     * @param {string} prompt Image prompt.
     * @param {Object} field  Field config: { aspect_ratio, gen_quality }.
     * @returns {{ api_url, headers, request_body }}
     */
    buildImageRequest(model, prompt, field) {
        const sizeMap = {
            square:    '1024x1024',
            landscape: '1792x1024',
            portrait:  '1024x1792',
        };
        const aspectRatio = field.aspect_ratio || 'square';
        const genQuality  = field.gen_quality  || 'medium';
        const size        = sizeMap[aspectRatio] || '1024x1024';

        return {
            api_url:      this._imageUrl,
            headers:      this._headers(),
            request_body: { model, prompt, size, quality: genQuality, n: 1 },
        };
    }

    /**
     * Extract generated text from an OpenAI chat completions response.
     * @param {Object} data Raw API JSON.
     * @returns {string|null}
     */
    parseTextResponse(data) {
        // /v1/responses format: output[].content[].text
        if (Array.isArray(data?.output)) {
            for (const item of data.output) {
                for (const part of (item.content || [])) {
                    if (part.type === 'output_text' || part.type === 'text') {
                        return part.text ?? null;
                    }
                }
            }
        }
        // /v1/completions returns choices[].text; /v1/chat/completions returns choices[].message.content.
        if (data?.choices?.[0]?.text !== undefined) {
            return data.choices[0].text ?? null;
        }
        return data?.choices?.[0]?.message?.content ?? null;
    }

    /**
     * Extract base64 image data from an OpenAI images response.
     * @param {Object} data Raw API JSON.
     * @returns {string|null}
     */
    parseImageResponse(data) {
        return data?.data?.[0]?.b64_json ?? null;
    }
}

// ── Anthropic ────────────────────────────────────────────────────────────────

class SparkPlusAnthropicProvider {
    constructor(apiKey) {
        this.apiKey   = apiKey;
        this._textUrl = 'https://api.anthropic.com/v1/messages';
    }

    _headers() {
        return {
            'x-api-key':         this.apiKey,
            'anthropic-version': '2023-06-01',
            'Content-Type':      'application/json',
        };
    }

    /**
     * Build fetch() params for a text generation request.
     *
     * @param {string} model  Model ID.
     * @param {string} prompt Full prompt string.
     * @returns {{ api_url, headers, request_body }}
     */
    buildTextRequest(model, prompt) {
        return {
            api_url:      this._textUrl,
            headers:      this._headers(),
            request_body: {
                model,
                max_tokens: 16000,
                messages:   [{ role: 'user', content: prompt }],
            },
        };
    }

    /**
     * Extract generated text from an Anthropic messages response.
     * @param {Object} data Raw API JSON.
     * @returns {string|null}
     */
    parseTextResponse(data) {
        return data?.content?.[0]?.text ?? null;
    }
}

// ── Gemini ───────────────────────────────────────────────────────────────────

class SparkPlusGeminiProvider {
    constructor(apiKey) {
        this.apiKey = apiKey;
    }

    _apiUrl(model) {
        return `https://generativelanguage.googleapis.com/v1beta/models/${encodeURIComponent(model)}:generateContent?key=${this.apiKey}`;
    }

    _headers() {
        return { 'Content-Type': 'application/json' };
    }

    /**
     * Build fetch() params for a text generation request.
     *
     * @param {string} model  Model ID.
     * @param {string} prompt Full prompt string.
     * @returns {{ api_url, headers, request_body }}
     */
    buildTextRequest(model, prompt) {
        return {
            api_url: this._apiUrl(model),
            headers: this._headers(),
            request_body: {
                contents:         [{ parts: [{ text: prompt }] }],
                generationConfig: {
                    maxOutputTokens:  16000,
                    temperature:      0.7,
                    responseMimeType: 'application/json',
                },
            },
        };
    }

    /**
     * Build fetch() params for an image generation request.
     *
     * The reference image (if any) is already pre-fetched server-side
     * and provided as `field.reference_image = { mime_type, b64_data }`.
     *
     * @param {string} model  Model ID.
     * @param {string} prompt Image prompt.
     * @param {Object} field  Field config: { aspect_ratio, output_resolution, reference_image }.
     * @returns {{ api_url, headers, request_body }}
     */
    buildImageRequest(model, prompt, field) {
        const aspectMap = {
            square:    '1:1',
            landscape: '16:9',
            portrait:  '9:16',
        };
        const resolutionMap = {
            low:    '1K',
            medium: '2K',
            high:   '4K',
        };

        const aspectRatio      = field.aspect_ratio      || 'square';
        const outputResolution = field.output_resolution || 'medium';
        const aspect           = aspectMap[aspectRatio]        || '1:1';
        const imageSize        = resolutionMap[outputResolution] || '2K';
        // gemini-2.5-flash-image does not support the imageSize param.
        const supportsSize     = (model !== 'gemini-2.5-flash-image');

        const imageConfig = { aspectRatio: aspect };
        if (supportsSize) {
            imageConfig.imageSize = imageSize;
        }

        const generationConfig = {
            responseModalities: ['IMAGE'],
            imageConfig,
        };

        // Start with text prompt part.
        const parts = [{ text: prompt }];

        // Prepend the pre-fetched reference image as inlineData (if provided).
        if (field.reference_image && field.reference_image.b64_data) {
            parts.unshift({
                inlineData: {
                    mimeType: field.reference_image.mime_type || 'image/jpeg',
                    data:     field.reference_image.b64_data,
                },
            });
        }

        return {
            api_url: this._apiUrl(model),
            headers: this._headers(),
            request_body: {
                contents:         [{ parts }],
                generationConfig,
            },
        };
    }

    /**
     * Extract generated text from a Gemini generateContent response.
     * @param {Object} data Raw API JSON.
     * @returns {string|null}
     */
    parseTextResponse(data) {
        return data?.candidates?.[0]?.content?.parts?.[0]?.text ?? null;
    }

    /**
     * Extract base64 image data from a Gemini generateContent response.
     * Skips thought parts and returns the first inlineData payload.
     * @param {Object} data Raw API JSON.
     * @returns {string|null}
     */
    parseImageResponse(data) {
        const parts = data?.candidates?.[0]?.content?.parts || [];
        for (const part of parts) {
            if (!part.thought && part.inlineData?.data) {
                return part.inlineData.data;
            }
        }
        return null;
    }
}

// ── Provider Factory ─────────────────────────────────────────────────────────

const SparkPlusProviderFactory = {

    /**
     * Supported model IDs grouped by type and provider.
     * Mirrors sparkplus_get_supported_models() in includes/util.php.
     */
    _models: {
        text: {
            openai:    ['gpt-5.5', 'gpt-5.5-pro', 'gpt-5.4', 'gpt-5.2', 'gpt-5.1', 'gpt-5-mini', 'gpt-5-nano'],
            anthropic: ['claude-opus-4-5', 'claude-sonnet-4-5', 'claude-haiku-3-5'],
            gemini:    ['gemini-3.1-pro-preview', 'gemini-3-flash-preview', 'gemini-3.1-flash-lite-preview', 'gemini-2.5-pro', 'gemini-2.5-flash', 'gemini-2.5-flash-lite'],
        },
        image: {
            openai: ['gpt-image-2', 'gpt-image-1.5', 'gpt-image-1', 'gpt-image-1-mini'],
            gemini: ['gemini-3.1-flash-image-preview', 'gemini-3-pro-image-preview', 'gemini-2.5-flash-image'],
        },
    },

    /**
     * Determine the provider slug for a given model ID and type.
     *
     * @param {string} modelId Model identifier.
     * @param {'text'|'image'} type
     * @returns {string|null} Provider slug or null if not found.
     */
    modelToProvider(modelId, type) {
        const byProvider = this._models[type] || {};
        for (const [slug, models] of Object.entries(byProvider)) {
            if (models.includes(modelId)) return slug;
        }
        // Fallback: prefix-based guess.
        if (modelId.startsWith('claude'))  return 'anthropic';
        if (modelId.startsWith('gemini'))  return 'gemini';
        return 'openai';
    },

    /**
     * Instantiate the text provider for the given slug.
     *
     * @param {string} providerSlug  e.g. 'openai', 'anthropic', 'gemini'.
     * @param {Object} apiKeys       Map of slug → key.
     * @returns {SparkPlusOpenAIProvider|SparkPlusAnthropicProvider|SparkPlusGeminiProvider}
     */
    getTextProvider(providerSlug, apiKeys) {
        switch (providerSlug) {
            case 'anthropic': return new SparkPlusAnthropicProvider(apiKeys.anthropic || '');
            case 'gemini':    return new SparkPlusGeminiProvider(apiKeys.gemini || '');
            default:          return new SparkPlusOpenAIProvider(apiKeys.openai || '');
        }
    },

    /**
     * Instantiate the image provider for the given slug.
     *
     * @param {string} providerSlug  e.g. 'openai', 'gemini'.
     * @param {Object} apiKeys       Map of slug → key.
     * @returns {SparkPlusOpenAIProvider|SparkPlusGeminiProvider}
     */
    getImageProvider(providerSlug, apiKeys) {
        switch (providerSlug) {
            case 'gemini': return new SparkPlusGeminiProvider(apiKeys.gemini || '');
            default:       return new SparkPlusOpenAIProvider(apiKeys.openai || '');
        }
    },
};

// Expose to global scope so generation.js can access these classes.
window.SparkPlusOpenAIProvider    = SparkPlusOpenAIProvider;
window.SparkPlusAnthropicProvider = SparkPlusAnthropicProvider;
window.SparkPlusGeminiProvider    = SparkPlusGeminiProvider;
window.SparkPlusProviderFactory   = SparkPlusProviderFactory;
