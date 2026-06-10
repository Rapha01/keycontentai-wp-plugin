/**
 * SparkPlus Prompt Builder
 *
 * JavaScript port of includes/prompt-builder.php.
 * All prompt construction happens entirely in the browser; the server only
 * passes raw settings and pre-built data (existing content, linking pool).
 *
 * Public API:
 *   buildTextPrompt(cptSettings, postSettings, textFields, existingContent, linking, wysiwygFormatting)
 *   buildImagePrompt(cptSettings, postSettings, imageField, existingContent)
 *   buildAltTextPrompt(imagePrompt)
 */

/* global window */

class SparkPlusPromptBuilder {

    // ── Language names (mirrors sparkplus_get_language_names() in util.php) ───

    static _languageNames = {
        // Germanic
        de: 'German', en: 'English', nl: 'Dutch', sv: 'Swedish', da: 'Danish',
        no: 'Norwegian', is: 'Icelandic', lb: 'Luxembourgish',
        // Romance
        fr: 'French', es: 'Spanish', it: 'Italian', pt: 'Portuguese',
        ro: 'Romanian', ca: 'Catalan', gl: 'Galician',
        // Slavic
        pl: 'Polish', cs: 'Czech', sk: 'Slovak', ru: 'Russian', uk: 'Ukrainian',
        bg: 'Bulgarian', sr: 'Serbian', hr: 'Croatian', sl: 'Slovenian',
        mk: 'Macedonian', bs: 'Bosnian', be: 'Belarusian',
        // Baltic
        lt: 'Lithuanian', lv: 'Latvian',
        // Celtic
        ga: 'Irish', cy: 'Welsh', gd: 'Scottish Gaelic', br: 'Breton',
        // Finno-Ugric
        fi: 'Finnish', et: 'Estonian', hu: 'Hungarian',
        // Other European
        el: 'Greek', sq: 'Albanian', hy: 'Armenian', eu: 'Basque', mt: 'Maltese',
        // Major non-European
        zh: 'Chinese', ja: 'Japanese', ko: 'Korean', ar: 'Arabic', tr: 'Turkish',
        he: 'Hebrew', hi: 'Hindi', th: 'Thai', vi: 'Vietnamese',
    };

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Build the complete text generation prompt.
     *
     * @param {Object}   cptSettings        CPT-level flat settings object.
     * @param {Object}   postSettings       Post-specific settings (keyword, post_additional_context, ...).
     * @param {Array}    textFields         Enabled text field configs (type !== 'image').
     * @param {Array}    existingContent    Pre-built [{label, value}] from server.
     * @param {Object|null} linking        Pre-built {enabled, wysiwyg, pool[]} or null.
     * @param {Object}   wysiwygFormatting  Options: {paragraphs, bold, italic, headings, lists}.
     * @returns {string} Full prompt string.
     */
    buildTextPrompt(cptSettings, postSettings, textFields, existingContent, linking, wysiwygFormatting) {
        if (!textFields || textFields.length === 0) return '';

        const parts = [
            this._buildSystemContext(),
            this._buildLanguageInstruction(cptSettings),
            this._buildTopicSection(postSettings),
            this._buildGeneralContext(cptSettings, 'text'),
            this._buildTargetAudienceSection(cptSettings),
            this._buildPostTypeContext(cptSettings, 'text'),
            this._buildPostSpecificContext(postSettings),
            this._buildExistingContentSection(existingContent, textFields, linking),
            this._buildInternalLinkingSection(linking, textFields),
            this._buildWysiwygFormattingRules(textFields, wysiwygFormatting),
            this._buildCustomFieldsInstructions(textFields, !!(cptSettings.include_acf_instructions)),
            this._buildOutputFormatInstructions(textFields),
            this._buildFinalInstructions(cptSettings),
        ];

        return parts.filter(Boolean).join('\n\n\n');
    }

    /**
     * Build the complete image generation prompt for a single image field.
     *
     * @param {Object} cptSettings   CPT-level settings.
     * @param {Object} postSettings  Post-specific settings (keyword, ...).
     * @param {Object} imageField    Image field config (from image_fields[]).
     * @param {Array}  existingContent Pre-built [{label, value}].
     * @returns {string} Image prompt string.
     */
    buildImagePrompt(cptSettings, postSettings, imageField, existingContent) {
        const keyword = postSettings.keyword || '';

        const parts = [
            this._buildImageFieldSection(imageField, cptSettings, keyword),
            this._buildTopicSection(postSettings),
            this._buildGeneralContext(cptSettings, 'image'),
            this._buildTargetAudienceSection(cptSettings),
            this._buildPostTypeContext(cptSettings, 'image'),
            this._buildPostSpecificContext(postSettings),
            this._buildImageStyleRequirements(),
            this._buildExistingContentForImage(existingContent),
        ];

        return parts.filter(Boolean).join('\n\n');
    }

    /**
     * Build an alt text generation prompt based on the image prompt.
     * The model is asked to return a JSON object with a single `alt_text` key.
     *
     * @param {string} imagePrompt The image generation prompt.
     * @returns {string} Alt text prompt.
     */
    buildAltTextPrompt(imagePrompt) {
        return [
            'Based on the following image generation prompt, write a concise and descriptive alt text for the image.',
            '',
            '## Image Prompt',
            imagePrompt,
            '',
            '## Requirements',
            '- Maximum 125 characters (including spaces).',
            '- Describe the image content accurately and concisely.',
            '- Do NOT include phrases like "image of" or "picture of".',
            '- Plain text only — no HTML, no markdown.',
            '',
            '## Output Format',
            'Return ONLY a valid JSON object with a single key:',
            '{"alt_text": "your concise alt text here"}',
        ].join('\n');
    }

    // ── Shared Sections ───────────────────────────────────────────────────────

    _buildSystemContext() {
        return 'You are an expert content writer and SEO specialist. Your task is to create high-quality, engaging, and SEO-optimized content for a professional website.';
    }

    _buildLanguageInstruction(cptSettings) {
        const code = cptSettings.language;
        if (!code) return '';

        const name        = SparkPlusPromptBuilder._languageNames[code] || (code.charAt(0).toUpperCase() + code.slice(1));
        let instruction   = `**IMPORTANT:** Write all content in ${name}.`;

        if (code === 'de' && cptSettings.addressing) {
            instruction += cptSettings.addressing === 'formal'
                ? ' Use formal addressing (Sie) when speaking to the reader.'
                : ' Use informal addressing (Du) when speaking to the reader.';
        }

        return instruction;
    }

    _buildTopicSection(postSettings) {
        if (!postSettings.keyword) return '';
        return `# Topic\n**Keyword:** ${postSettings.keyword}`;
    }

    _buildGeneralContext(cptSettings, type) {
        const parts = [];

        if (cptSettings.company_name)   parts.push(`- Company: ${cptSettings.company_name}`);
        if (cptSettings.industry)       parts.push(`- Industry: ${cptSettings.industry}`);
        if (cptSettings.usp)            parts.push(`- Unique Selling Proposition: ${cptSettings.usp}`);
        if (cptSettings.advantages)     parts.push(`- Key Advantages: ${cptSettings.advantages}`);
        if (cptSettings.buying_reasons) parts.push(`- Why Customers Choose Us: ${cptSettings.buying_reasons}`);

        const key = (type === 'image')
            ? 'general_context_additional_context_image'
            : 'general_context_additional_context_text';

        if (cptSettings[key]) parts.push(`- Additional Context: ${cptSettings[key]}`);

        if (parts.length === 0) return '';

        return ['# General Context', ...parts].join('\n');
    }

    _buildTargetAudienceSection(cptSettings) {
        if (!cptSettings.target_group) return '';
        return `# Target Audience\n${cptSettings.target_group}\nTailor the content to resonate with this specific audience. Use language, examples, and references that appeal to them.`;
    }

    _buildPostTypeContext(cptSettings, type) {
        const parts = [`# Post Type: ${cptSettings.post_type}`];
        const key   = (type === 'image') ? 'cpt_additional_context_image' : 'cpt_additional_context_text';
        if (cptSettings[key]) {
            parts.push('## Specific Instructions for This Post Type');
            parts.push(cptSettings[key]);
        }
        return parts.join('\n');
    }

    _buildPostSpecificContext(postSettings) {
        if (!postSettings.post_additional_context) return '';
        return [
            '# Post-Specific Instructions',
            postSettings.post_additional_context,
            '\n**These instructions are specific to this individual post and should take priority over general instructions.**',
        ].join('\n');
    }

    /**
     * Build the existing content section for text prompts.
     *
     * @param {Array}        existingContent Pre-built [{label, value}].
     * @param {Array}        textFields      Text field configs.
     * @param {Object|null}  linking         Linking data or null.
     */
    _buildExistingContentSection(existingContent, textFields, linking) {
        if (!existingContent || existingContent.length === 0) return '';

        const items = existingContent.map(entry => {
            // Collapse newlines so value stays on a single Markdown list line.
            const clean = String(entry.value).replace(/\s*\n\s*/g, ' ').replace(/ {2,}/g, ' ').trim();
            return `- ${entry.label}: ${clean}`;
        });

        const parts = [
            '# Existing Content',
            'This post already has content in the following fields:',
            '',
            ...items,
            '',
            '## Purpose',
            'Use this existing content to:',
            '1. **Avoid duplication:** Don\'t repeat information already covered in other fields',
            '2. **Reference between fields:** You can reference information from other fields when relevant (e.g., \'As mentioned in the description...\')',
            '3. **Reference from field context:** If the additional context/description of a field being generated instructs you to reference other fields, you may use the content above',
        ];

        if (linking && linking.enabled) {
            parts.push('4. **Link management:** Check what links already exist in other fields to avoid overusing links to the same page, but do add new relevant links from the linking pool that haven\'t been used yet');
        }

        return parts.join('\n');
    }

    // ── Text-Specific Sections ────────────────────────────────────────────────

    /**
     * Build internal linking section from pre-built linking data.
     *
     * @param {Object|null} linking    { enabled, wysiwyg, pool[] } or null.
     * @param {Array}       textFields Text field configs.
     */
    _buildInternalLinkingSection(linking, textFields) {
        if (!linking || !linking.enabled || !linking.pool || linking.pool.length === 0) return '';

        const parts = [
            '# Internal Linking',
            'You have access to the following links that you should use when appropriate. **ONLY use links from this list** - do not create or use any other links:',
            '',
        ];

        linking.pool.forEach((link, index) => {
            let entry = `${index + 1}. `;
            if (link.keyword) entry += `Keyword: ${link.keyword} | `;
            entry += `Title: ${link.title} | URL: ${link.url}`;
            if (link.id) entry += ` | ID: ${link.id}`;
            parts.push(entry);
        });

        parts.push('');

        const hasWysiwyg = textFields.some(f => f.type === 'wysiwyg');
        const hasUrl     = textFields.some(f => f.type === 'url');

        parts.push('## How to Use These Links');
        parts.push('**IMPORTANT:** You must **ONLY** use URLs from the list above. Never use or create links that are not explicitly provided in this list.');
        parts.push('Actively look for opportunities to include relevant links from this list. Link to related topics, broader concepts, or complementary information that would benefit the reader.');
        parts.push('');

        if (linking.wysiwyg && hasWysiwyg) {
            parts.push('- For WYSIWYG fields: Insert relevant HTML links (<a href="URL">anchor text</a>) throughout the content. Link to related pages when:');
            parts.push('  \u2022 Mentioning a topic that has a dedicated page in the list');
            parts.push('  \u2022 Discussing a specific aspect of a broader topic (e.g., link from \'Vogelmilben bek\u00e4mpfen\' to general \'Vogelmilben\' page)');
            parts.push('  \u2022 Referencing related products, services, or information covered by other pages');
            parts.push('  \u2022 Providing context or background that another page explains in detail');
            parts.push('  Use contextually appropriate anchor text and aim to include multiple relevant links where it makes sense. Use ONLY the exact URLs provided in the list.');
        }

        if (hasUrl) {
            parts.push('- For URL fields: Select the most relevant link from the list above based on the field\'s purpose and the content you\'re generating. Look for strong topical relationships and choose links that provide valuable additional context or information. Use ONLY URLs from the provided list.');
        }

        if (!linking.wysiwyg && !hasUrl) {
            parts.push('- Use these links as reference material to understand the available content, but do not insert them into the generated content.');
        }

        return parts.join('\n');
    }

    /**
     * Build WYSIWYG formatting rules.
     *
     * @param {Array}  textFields         Field configs.
     * @param {Object} wysiwygFormatting  Options: {paragraphs, bold, italic, headings, lists}.
     */
    _buildWysiwygFormattingRules(textFields, wysiwygFormatting) {
        if (!textFields.some(f => f.type === 'wysiwyg')) return '';

        const fmt = wysiwygFormatting || { paragraphs: true, bold: true, italic: true, headings: false, lists: true };
        const allowed = this._buildAllowedHtmlTags(fmt);
        if (!allowed) return '';

        const parts = [
            '# WYSIWYG Field Formatting',
            'For fields marked as type \'wysiwyg\', you may use HTML formatting when it enhances readability and structure.',
            `Available HTML tags: ${allowed}`,
            '',
            'Use HTML formatting when appropriate:',
        ];

        if (fmt.paragraphs) parts.push('- <p> tags for paragraph breaks when content has multiple paragraphs');
        if (fmt.headings)   parts.push('- <h2>, <h3>, <h4> for section headings when content benefits from clear structure');
        if (fmt.bold)       parts.push('- <strong> to emphasize important points or key terms when needed');
        if (fmt.italic)     parts.push('- <em> for subtle emphasis or technical terms when appropriate');
        if (fmt.lists)      parts.push('- <ul>/<ol> with <li> for lists when presenting multiple items or steps');

        parts.push('');
        parts.push('Plain text is perfectly acceptable if HTML formatting doesn\'t add value. Use your judgment to create well-structured, readable content.');

        return parts.join('\n');
    }

    /**
     * Build the list of field instructions for the text prompt.
     *
     * @param {Array}   textFields          Field configs.
     * @param {boolean} includeAcfInstructions
     */
    _buildCustomFieldsInstructions(textFields, includeAcfInstructions) {
        const instructions = [
            '# Content Fields to Generate',
            'You must generate content for the following custom fields:',
            '',
        ];

        // Field type guide — only show types actually present in the list.
        const presentTypes = new Set(textFields.map(f => f.type));
        const typeGuide = {
            text:        '**text** \u2014 Short plain text. Output a plain text string; no HTML or markdown.',
            textarea:    '**textarea** \u2014 Multi-line plain text. Use \\n for line breaks; no HTML.',
            wysiwyg:     '**wysiwyg** \u2014 Rich text editor. HTML formatting is allowed (see WYSIWYG Field Formatting rules above).',
            true_false:  '**true_false** \u2014 Boolean. You MUST output the integer `1` (true/yes) or `0` (false/no). No other value is accepted.',
            post_object: '**post_object** \u2014 Related post(s). Output a JSON array of integer post IDs chosen from the Internal Linking pool. Example: `[12, 34]`. Only use IDs listed in the linking section.',
        };

        const guideLines = Object.entries(typeGuide)
            .filter(([t]) => presentTypes.has(t))
            .map(([, desc]) => `- ${desc}`);

        if (guideLines.length > 0) {
            instructions.push('## Field Type Guide');
            guideLines.forEach(l => instructions.push(l));
            instructions.push('');
        }

        let fieldNum     = 1;
        let currentGroup = null;
        let subNum       = 1;

        for (const field of textFields) {
            const thisGroup = field.group_key || null;

            // Emit group header when entering a new group.
            if (thisGroup !== null && thisGroup !== currentGroup) {
                const groupLabel = field.group_label || thisGroup;
                instructions.push(`${fieldNum}. **Group: ${groupLabel} (${thisGroup})**`);
                fieldNum++;
                subNum       = 1;
                currentGroup = thisGroup;
            } else if (thisGroup === null) {
                currentGroup = null;
            }

            if (thisGroup !== null) {
                instructions.push(`   ${subNum}. Field: ${field.label} (${field.key})`);
                instructions.push(`      Field Type: ${field.type}`);
                if (includeAcfInstructions && field.acf_instructions) {
                    instructions.push(`      ACF Instructions: ${field.acf_instructions}`);
                }
                if (field.description) {
                    instructions.push(`      Description: ${field.description}`);
                }
                const rmHint = this._getRankMathFieldInstructions(field.key);
                if (rmHint) instructions.push(rmHint);
                if (field.word_count > 0) {
                    if (field.type === 'post_object') {
                        instructions.push(`      Select approximately ${field.word_count} posts from the Internal Linking pool`);
                    } else {
                        instructions.push(`      Target Word Count: approximately ${field.word_count} words`);
                    }
                }
                instructions.push('');
                subNum++;
            } else {
                instructions.push(`${fieldNum}. Field: ${field.label} (${field.key})`);
                instructions.push(`   Field Type: ${field.type}`);
                if (includeAcfInstructions && field.acf_instructions) {
                    instructions.push(`   ACF Instructions: ${field.acf_instructions}`);
                }
                if (field.description) {
                    instructions.push(`   Description: ${field.description}`);
                }
                const rmHint = this._getRankMathFieldInstructions(field.key);
                if (rmHint) instructions.push(rmHint);
                if (field.word_count > 0) {
                    if (field.type === 'post_object') {
                        instructions.push(`   Select approximately ${field.word_count} posts from the Internal Linking pool`);
                    } else {
                        instructions.push(`   Target Word Count: approximately ${field.word_count} words`);
                    }
                }
                instructions.push('');
                fieldNum++;
            }
        }

        return instructions.join('\n');
    }

    /**
     * Build the output format / JSON template section.
     *
     * @param {Array} textFields Field configs.
     */
    _buildOutputFormatInstructions(textFields) {
        const instructions = [
            '# Output Format Requirements',
            'You **MUST** return the content as a valid JSON object with the following structure:',
            '',
            '{',
        ];

        // Build ordered output items, nesting group sub-fields as objects.
        const outputItems  = [];
        const seenGroups   = {};

        for (const field of textFields) {
            if (field.group_key) {
                const gk = field.group_key;
                if (seenGroups[gk] === undefined) {
                    seenGroups[gk] = outputItems.length;
                    outputItems.push({ type: 'group', key: gk, subKeys: [] });
                }
                outputItems[seenGroups[gk]].subKeys.push({ key: field.key, fieldType: field.type });
            } else {
                outputItems.push({ type: 'field', key: field.key, fieldType: field.type });
            }
        }

        outputItems.forEach((item, i) => {
            const comma = (i < outputItems.length - 1) ? ',' : '';
            if (item.type === 'group') {
                instructions.push(`  "${item.key}": {`);
                item.subKeys.forEach((sub, j) => {
                    const subComma = (j < item.subKeys.length - 1) ? ',' : '';
                    instructions.push(`    "${sub.key}": ${this._getJsonPlaceholder(sub.fieldType)}${subComma}`);
                });
                instructions.push(`  }${comma}`);
            } else {
                instructions.push(`  "${item.key}": ${this._getJsonPlaceholder(item.fieldType)}${comma}`);
            }
        });

        instructions.push('}');
        instructions.push('');
        instructions.push('## Critical JSON Formatting Rules');
        instructions.push('- Return ONLY valid JSON, no additional text before or after');
        instructions.push('- Use double quotes for all keys and string values');
        instructions.push('- Properly escape special characters (quotes, newlines, etc.)');
        instructions.push('- For WYSIWYG fields: include HTML tags as specified in the field instructions');
        instructions.push('- For non-WYSIWYG text fields: use plain text with \\n for line breaks, no HTML');
        instructions.push('- For true_false fields: output the integer 1 (true/yes) or 0 (false/no) \u2014 no quotes, no other values');
        instructions.push('- For post_object fields: output a JSON array of integer post IDs (e.g. [12, 34]) \u2014 only use IDs from the Internal Linking pool');
        instructions.push('- All field values must be strings, except true_false fields (integers 1 or 0) and post_object fields (arrays of integers)');

        return instructions.join('\n');
    }

    _buildFinalInstructions(cptSettings) {
        const parts = [
            '# Quality Requirements',
            '- Write in a professional, engaging tone',
            '- Ensure content is SEO-optimized with natural keyword usage',
            '- Use clear, concise language appropriate for the target audience',
            '- Include relevant examples and details where appropriate',
            '- Maintain factual accuracy and credibility',
            '- Follow best practices for web content writing',
        ];

        if (cptSettings.company_name) {
            parts.push('- Naturally incorporate the company name where relevant');
        }

        parts.push('');
        parts.push('Remember: Return ONLY the JSON object, no explanations or additional text.');

        return parts.join('\n');
    }

    // ── Image-Specific Sections ───────────────────────────────────────────────

    _buildImageFieldSection(field, cptSettings, keyword) {
        const parts = [
            `# Image Field: ${field.label}`,
            `**Field key:** \`${field.key}\``,
        ];

        const useAcf    = !!(cptSettings.include_acf_instructions) && !!(field.acf_instructions);
        const hasCustom = !!(field.description) || useAcf;

        if (hasCustom) {
            const lines = [];
            if (useAcf)          lines.push(field.acf_instructions);
            if (field.description) lines.push(field.description);
            parts.push('# Specific Instructions\n' + lines.join('\n'));
        } else {
            parts.push(`# Instructions\nCreate a professional, high-quality image that represents the field '${field.label}' in the context of: ${keyword}`);
        }

        return parts.join('\n');
    }

    _buildImageStyleRequirements() {
        return '# Style Requirements\n'
            + '- Professional and modern\n'
            + '- High-quality composition\n'
            + '- Clear and well-lit\n'
            + '- Suitable for commercial use\n'
            + '- Match the tone and context of the post content';
    }

    /**
     * Build existing-content reference section for image prompts.
     * Takes the same pre-built [{label, value}] array as text prompts.
     *
     * @param {Array} existingContent Pre-built [{label, value}].
     */
    _buildExistingContentForImage(existingContent) {
        if (!existingContent || existingContent.length === 0) return '';

        const contentLines = existingContent.map(entry => `[${entry.label}]\n${entry.value}`).join('\n\n');

        return '# Existing Post Content (Reference Only)\n'
            + 'The following content is provided as additional context and may help you better understand '
            + 'the topic and tone of the image to generate. You may reference this content if the instructions '
            + 'above mention specific fields or require context from the post. However, this is optional '
            + 'reference material \u2014 focus primarily on the instructions and requirements specified above.\n\n'
            + contentLines;
    }

    // ── Private Utilities ─────────────────────────────────────────────────────

    /**
     * Return special copy-writing instructions for RankMath meta fields.
     *
     * @param {string} key Field key.
     * @returns {string} Instruction string or empty string.
     */
    _getRankMathFieldInstructions(key) {
        if (key === 'post_slug') {
            return [
                '   \u2691 URL Slug \u2014 rules:',
                '     \u2022 Lowercase only. Use hyphens to separate words \u2014 no underscores, spaces, or special characters.',
            ].join('\n');
        }
        if (key === 'rank_math_title') {
            return [
                '   \u2691 SEO Meta Title \u2014 follow these rules strictly:',
                '     \u2022 Maximum 60 characters (including spaces). Character counts close to the maximum are better. Count carefully.',
                '     \u2022 Include the primary keyword naturally \u2014 it does NOT have to be the very first word but earlier mentions of the keyword are preferrable.',
                '     \u2022 Internally write 3 candidate titles using clearly different structures. Do NOT output these candidates \u2014 output only the third one as the final value.',
                '     \u2022 Avoid generic, formulaic phrasing. The title should stand out and make the reader want to click.',
                '     \u2022 Do NOT append the site/brand name \u2014 RankMath adds that automatically.',
                '     \u2022 Do NOT use quotation marks, pipes, or special characters.',
            ].join('\n');
        }
        if (key === 'rank_math_description') {
            return [
                '   \u2691 SEO Meta Description \u2014 follow these rules strictly:',
                '     \u2022 Between 145 and 150 characters (including spaces). Count carefully.',
                '     \u2022 Include the primary keyword naturally in the first half of the sentence.',
                '     \u2022 Write a single clear, action-oriented sentence that summarises the page and encourages click-through.',
                '     \u2022 Do NOT use quotation marks or markdown formatting.',
                '     \u2022 Do NOT truncate \u2014 the sentence must feel complete.',
            ].join('\n');
        }
        return '';
    }

    /**
     * Return JSON template placeholder for a given ACF field type.
     *
     * @param {string} fieldType ACF field type.
     * @returns {string} JSON placeholder.
     */
    _getJsonPlaceholder(fieldType) {
        switch (fieldType) {
            case 'true_false':  return '1';
            case 'post_object': return '[12, 34]';
            default:            return '"content for this field"';
        }
    }

    /**
     * Build a comma-separated string of allowed HTML tags based on formatting options.
     *
     * @param {Object} fmt Formatting options.
     * @returns {string}
     */
    _buildAllowedHtmlTags(fmt) {
        const tags = [];
        if (fmt.paragraphs) tags.push('<p>');
        if (fmt.bold)       tags.push('<strong>');
        if (fmt.italic)     tags.push('<em>');
        if (fmt.headings)   tags.push('<h2>', '<h3>', '<h4>');
        if (fmt.lists)      tags.push('<ul>', '<ol>', '<li>');
        return tags.join(', ');
    }
}

// Expose to global scope.
window.SparkPlusPromptBuilder = SparkPlusPromptBuilder;
