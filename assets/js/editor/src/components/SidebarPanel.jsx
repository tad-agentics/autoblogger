/**
 * AutoBlogger Sidebar Panel
 * Main interface for AI content generation in Gutenberg editor
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl, SelectControl, TextareaControl, Button, Notice } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

const SidebarPanel = () => {
    const [keyword, setKeyword] = useState('');
    const [persona, setPersona] = useState('academic');
    const [humanStory, setHumanStory] = useState('');
    const [outline, setOutline] = useState('');
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [costEstimate, setCostEstimate] = useState(null);
    const [versions, setVersions] = useState([]);
    const [loadingVersions, setLoadingVersions] = useState(false);

    const { editPost } = useDispatch('core/editor');
    const postId = useSelect(select => select('core/editor').getCurrentPostId());
    const postContent = useSelect(select => select('core/editor').getEditedPostContent());

    // Load version history
    useEffect(() => {
        if (postId) {
            loadVersions();
        }
    }, [postId]);

    const loadVersions = async () => {
        setLoadingVersions(true);
        try {
            const response = await apiFetch({
                path: `/autoblogger/v1/versions/${postId}`,
                method: 'GET'
            });
            
            if (response.success) {
                setVersions(response.data || []);
            }
        } catch (error) {
            console.error('Failed to load versions:', error);
        } finally {
            setLoadingVersions(false);
        }
    };

    const handleRestoreVersion = async (versionIndex) => {
        if (!confirm(__('Restore this version? Current content will be saved first.', 'autoblogger'))) {
            return;
        }

        try {
            const response = await apiFetch({
                path: `/autoblogger/v1/versions/${postId}/restore`,
                method: 'POST',
                data: { version_index: versionIndex }
            });

            if (response.success) {
                // Reload the post content
                window.location.reload();
            } else {
                setMessage({ type: 'error', text: response.message });
            }
        } catch (error) {
            console.error('Restore failed:', error);
            setMessage({ type: 'error', text: __('Failed to restore version', 'autoblogger') });
        }
    };

    // Estimate cost based on keyword length
    useEffect(() => {
        if (keyword) {
            const estimatedTokens = keyword.length * 1.3 * 50; // Rough estimate
            const estimatedCost = (estimatedTokens / 1000000) * 3; // Input cost
            setCostEstimate(estimatedCost);
        } else {
            setCostEstimate(null);
        }
    }, [keyword]);

    const getCostColor = () => {
        if (!costEstimate) return '#666';
        if (costEstimate < 0.1) return '#46b450'; // Green
        if (costEstimate < 0.5) return '#ffb900'; // Yellow
        return '#dc3232'; // Red
    };

    const handleGenerateDraft = async () => {
        if (!keyword) {
            setMessage({ type: 'error', text: __('Please enter a keyword', 'autoblogger') });
            return;
        }

        setLoading(true);
        setMessage(null);

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/generate/draft',
                method: 'POST',
                data: {
                    post_id: postId,
                    keyword,
                    persona,
                    human_story: humanStory,
                    outline: outline || null
                }
            });

            if (response.success) {
                // Insert content into editor
                editPost({ content: response.content });
                setMessage({ 
                    type: 'success', 
                    text: __('Draft generated successfully!', 'autoblogger') + ` (${__('Cost', 'autoblogger')}: $${response.cost.toFixed(4)})`
                });
            } else {
                setMessage({ type: 'error', text: response.message || __('Generation failed', 'autoblogger') });
            }
        } catch (error) {
            console.error('Generation failed:', error);
            setMessage({ type: 'error', text: error.message || __('Generation failed', 'autoblogger') });
        } finally {
            setLoading(false);
        }
    };

    const handleGenerateOutline = async () => {
        if (!keyword) {
            setMessage({ type: 'error', text: __('Please enter a keyword', 'autoblogger') });
            return;
        }

        setLoading(true);
        setMessage(null);

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/generate/outline',
                method: 'POST',
                data: {
                    post_id: postId,
                    keyword,
                    persona
                }
            });

            if (response.success) {
                setOutline(response.outline);
                setMessage({ type: 'success', text: __('Outline generated!', 'autoblogger') });
            } else {
                setMessage({ type: 'error', text: response.message });
            }
        } catch (error) {
            console.error('Outline generation failed:', error);
            setMessage({ type: 'error', text: __('Outline generation failed', 'autoblogger') });
        } finally {
            setLoading(false);
        }
    };

    const handleOptimizeSEO = async () => {
        if (!postContent) {
            setMessage({ type: 'error', text: __('No content to optimize', 'autoblogger') });
            return;
        }

        setLoading(true);
        setMessage(null);

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/optimize',
                method: 'POST',
                data: {
                    post_id: postId,
                    content: postContent,
                    keyword: keyword || 'SEO'
                }
            });

            if (response.success) {
                editPost({ content: response.content });
                setMessage({ 
                    type: 'success', 
                    text: __('Content optimized!', 'autoblogger') + ` (${response.iterations} ${__('iterations', 'autoblogger')})`
                });
            } else {
                setMessage({ type: 'error', text: response.message });
            }
        } catch (error) {
            console.error('Optimization failed:', error);
            setMessage({ type: 'error', text: __('Optimization failed', 'autoblogger') });
        } finally {
            setLoading(false);
        }
    };

    const handleWriteSection = async () => {
        const sectionTitle = prompt(__('Enter section title/heading:', 'autoblogger'));
        if (!sectionTitle) return;

        setLoading(true);
        setMessage(null);

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/generate/section',
                method: 'POST',
                data: {
                    post_id: postId,
                    section_title: sectionTitle,
                    keyword: keyword || sectionTitle,
                    persona
                }
            });

            if (response.success) {
                // Append to current content
                const newContent = postContent + '\n\n' + response.content;
                editPost({ content: newContent });
                setMessage({ 
                    type: 'success', 
                    text: __('Section added!', 'autoblogger') + ` ($${response.cost.toFixed(4)})`
                });
            } else {
                setMessage({ type: 'error', text: response.message });
            }
        } catch (error) {
            console.error('Section generation failed:', error);
            setMessage({ type: 'error', text: __('Section generation failed', 'autoblogger') });
        } finally {
            setLoading(false);
        }
    };

    const handleExpandText = async () => {
        // Get selected text from editor
        const selection = window.getSelection().toString();
        if (!selection) {
            setMessage({ type: 'error', text: __('Please select text to expand', 'autoblogger') });
            return;
        }

        setLoading(true);
        setMessage(null);

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/expand',
                method: 'POST',
                data: {
                    post_id: postId,
                    text: selection,
                    keyword: keyword || '',
                    persona
                }
            });

            if (response.success) {
                // Replace selection with expanded text
                // Note: This is a simplified approach. In production, you'd want to 
                // properly handle the block editor's content replacement
                const newContent = postContent.replace(selection, response.content);
                editPost({ content: newContent });
                setMessage({ 
                    type: 'success', 
                    text: __('Text expanded!', 'autoblogger') + ` ($${response.cost.toFixed(4)})`
                });
            } else {
                setMessage({ type: 'error', text: response.message });
            }
        } catch (error) {
            console.error('Text expansion failed:', error);
            setMessage({ type: 'error', text: __('Text expansion failed', 'autoblogger') });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="autoblogger-sidebar">
            {message && (
                <Notice 
                    status={message.type === 'error' ? 'error' : 'success'}
                    isDismissible={true}
                    onRemove={() => setMessage(null)}
                >
                    {message.text}
                </Notice>
            )}

            <PanelBody title={__('Topic Input', 'autoblogger')} initialOpen={true}>
                <TextControl
                    label={__('Main Keyword', 'autoblogger')}
                    value={keyword}
                    onChange={setKeyword}
                    placeholder={__('e.g., Sao Phá Quân', 'autoblogger')}
                    help={__('Enter the main topic or keyword for your article', 'autoblogger')}
                />

                <SelectControl
                    label={__('Writing Tone', 'autoblogger')}
                    value={persona}
                    options={[
                        { label: __('Academic', 'autoblogger'), value: 'academic' },
                        { label: __('Casual', 'autoblogger'), value: 'casual' },
                        { label: __('Professional', 'autoblogger'), value: 'professional' },
                        { label: __('Friendly', 'autoblogger'), value: 'friendly' }
                    ]}
                    onChange={setPersona}
                />

                <TextareaControl
                    label={__('Human Story (Optional)', 'autoblogger')}
                    value={humanStory}
                    onChange={setHumanStory}
                    placeholder={__('Add a personal experience or story...', 'autoblogger')}
                    help={__('This helps with E-E-A-T compliance', 'autoblogger')}
                    rows={3}
                />

                <TextareaControl
                    label={__('Custom Outline (Optional)', 'autoblogger')}
                    value={outline}
                    onChange={setOutline}
                    placeholder={__('Leave empty to auto-generate', 'autoblogger')}
                    rows={4}
                />

                {costEstimate && (
                    <div style={{ 
                        padding: '10px', 
                        background: '#f0f0f1', 
                        borderRadius: '4px',
                        marginBottom: '10px'
                    }}>
                        <strong>{__('Estimated Cost:', 'autoblogger')}</strong>{' '}
                        <span style={{ color: getCostColor(), fontWeight: 'bold' }}>
                            ${costEstimate.toFixed(4)}
                        </span>
                    </div>
                )}
            </PanelBody>

            <PanelBody title={__('Actions', 'autoblogger')} initialOpen={true}>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                    <Button
                        variant="secondary"
                        onClick={handleGenerateOutline}
                        disabled={loading || !keyword}
                        style={{ justifyContent: 'center' }}
                    >
                        {__('Generate Outline', 'autoblogger')}
                    </Button>

                    <Button
                        variant="primary"
                        onClick={handleGenerateDraft}
                        disabled={loading || !keyword}
                        isBusy={loading}
                        style={{ justifyContent: 'center' }}
                    >
                        {loading ? __('Generating...', 'autoblogger') : __('Generate Draft', 'autoblogger')}
                    </Button>

                    <Button
                        variant="secondary"
                        onClick={handleWriteSection}
                        disabled={loading}
                        style={{ justifyContent: 'center' }}
                    >
                        {__('Write Section', 'autoblogger')}
                    </Button>

                    <Button
                        variant="secondary"
                        onClick={handleExpandText}
                        disabled={loading}
                        style={{ justifyContent: 'center' }}
                    >
                        {__('Expand Selected Text', 'autoblogger')}
                    </Button>
                </div>
            </PanelBody>

            <PanelBody title={__('SEO Doctor', 'autoblogger')} initialOpen={false}>
                <p style={{ fontSize: '12px', color: '#666', marginBottom: '10px' }}>
                    {__('Automatically optimize your content for better SEO scores.', 'autoblogger')}
                </p>
                
                <Button
                    variant="secondary"
                    onClick={handleOptimizeSEO}
                    disabled={loading || !postContent}
                    isBusy={loading}
                    style={{ width: '100%', justifyContent: 'center' }}
                >
                    {loading ? __('Optimizing...', 'autoblogger') : __('Fix SEO Errors', 'autoblogger')}
                </Button>

                <p style={{ fontSize: '11px', color: '#999', marginTop: '10px' }}>
                    {__('Works with RankMath. Max 2 iterations.', 'autoblogger')}
                </p>
            </PanelBody>

            <PanelBody title={__('Version History', 'autoblogger')} initialOpen={false}>
                {loadingVersions ? (
                    <p style={{ fontSize: '12px', color: '#666' }}>{__('Loading...', 'autoblogger')}</p>
                ) : versions.length === 0 ? (
                    <p style={{ fontSize: '12px', color: '#666' }}>
                        {__('No version history yet. Versions are saved automatically when AI modifies content.', 'autoblogger')}
                    </p>
                ) : (
                    <div style={{ fontSize: '12px' }}>
                        {versions.map((version, index) => (
                            <div 
                                key={index} 
                                style={{ 
                                    padding: '10px',
                                    marginBottom: '10px',
                                    background: '#f6f7f7',
                                    borderRadius: '4px',
                                    borderLeft: '3px solid #2271b1'
                                }}
                            >
                                <div style={{ fontWeight: 'bold', marginBottom: '5px' }}>
                                    {version.operation || __('Edit', 'autoblogger')}
                                </div>
                                <div style={{ color: '#666', marginBottom: '5px' }}>
                                    {new Date(version.timestamp).toLocaleString()}
                                </div>
                                <div style={{ marginBottom: '8px', maxHeight: '60px', overflow: 'hidden', color: '#666' }}>
                                    {version.content.substring(0, 100)}...
                                </div>
                                <Button
                                    variant="secondary"
                                    isSmall
                                    onClick={() => handleRestoreVersion(index)}
                                >
                                    {__('Restore', 'autoblogger')}
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
            </PanelBody>

            <PanelBody title={__('Help', 'autoblogger')} initialOpen={false}>
                <div style={{ fontSize: '12px', color: '#666' }}>
                    <p><strong>{__('Quick Guide:', 'autoblogger')}</strong></p>
                    <ol style={{ paddingLeft: '20px', margin: '10px 0' }}>
                        <li>{__('Enter your main keyword', 'autoblogger')}</li>
                        <li>{__('Choose writing tone', 'autoblogger')}</li>
                        <li>{__('Click "Generate Draft"', 'autoblogger')}</li>
                        <li>{__('Review and edit the content', 'autoblogger')}</li>
                        <li>{__('Use "Fix SEO Errors" if needed', 'autoblogger')}</li>
                    </ol>
                    <p style={{ marginTop: '10px' }}>
                        <a href="admin.php?page=autoblogger" target="_blank">
                            {__('Configure Settings', 'autoblogger')} →
                        </a>
                    </p>
                </div>
            </PanelBody>
        </div>
    );
};

export default SidebarPanel;

