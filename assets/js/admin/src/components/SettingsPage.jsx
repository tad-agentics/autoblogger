/**
 * Settings Page Component
 * Handles API configuration, model selection, and plugin settings
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const SettingsPage = () => {
    const [settings, setSettings] = useState({
        api_key: '',
        api_provider: 'claude',
        api_model: 'claude-3-5-sonnet-20241022',
        daily_budget: 5.00,
        max_optimization_iterations: 2,
        disclaimer_text: 'This information is for reference purposes only. For health/financial matters, please consult qualified professionals.',
        system_prompt: '',
        personas: [],
        negative_keywords: []
    });
    
    const [prompts, setPrompts] = useState({
        'generate-draft': '',
        'generate-outline': '',
        'generate-section': '',
        'optimize-content': '',
        'expand-text': ''
    });
    
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [savingPrompts, setSavingPrompts] = useState(false);
    const [message, setMessage] = useState(null);
    const [activeTab, setActiveTab] = useState('api');

    // Load settings and prompts on mount
    useEffect(() => {
        loadSettings();
        loadPrompts();
    }, []);

    const loadSettings = async () => {
        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/settings',
                method: 'GET'
            });
            
            if (response.success) {
                setSettings(response.data);
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
            setMessage({ type: 'error', text: __('Failed to load settings', 'autoblogger') });
        } finally {
            setLoading(false);
        }
    };

    const loadPrompts = async () => {
        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/prompts',
                method: 'GET'
            });
            
            if (response.success) {
                setPrompts(response.data);
            }
        } catch (error) {
            console.error('Failed to load prompts:', error);
        }
    };

    const saveSettings = async (e) => {
        e.preventDefault();
        setSaving(true);
        setMessage(null);

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/settings',
                method: 'POST',
                data: settings
            });

            if (response.success) {
                setMessage({ type: 'success', text: __('Settings saved successfully!', 'autoblogger') });
            } else {
                setMessage({ type: 'error', text: response.message || __('Failed to save settings', 'autoblogger') });
            }
        } catch (error) {
            console.error('Failed to save settings:', error);
            setMessage({ type: 'error', text: __('Failed to save settings', 'autoblogger') });
        } finally {
            setSaving(false);
        }
    };

    const handleInputChange = (field, value) => {
        setSettings(prev => ({ ...prev, [field]: value }));
    };

    const handlePromptChange = (template, value) => {
        setPrompts(prev => ({ ...prev, [template]: value }));
    };

    const savePrompts = async (e) => {
        e.preventDefault();
        setSavingPrompts(true);
        setMessage(null);

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/prompts',
                method: 'POST',
                data: prompts
            });

            if (response.success) {
                setMessage({ type: 'success', text: __('Prompts saved successfully!', 'autoblogger') });
            } else {
                setMessage({ type: 'error', text: response.message || __('Failed to save prompts', 'autoblogger') });
            }
        } catch (error) {
            console.error('Failed to save prompts:', error);
            setMessage({ type: 'error', text: __('Failed to save prompts', 'autoblogger') });
        } finally {
            setSavingPrompts(false);
        }
    };

    if (loading) {
        return <div className="autoblogger-loading">{__('Loading settings...', 'autoblogger')}</div>;
    }

    return (
        <div className="autoblogger-settings">
            {message && (
                <div className={`notice notice-${message.type} is-dismissible`}>
                    <p>{message.text}</p>
                </div>
            )}

            <div className="nav-tab-wrapper">
                <button 
                    className={`nav-tab ${activeTab === 'api' ? 'nav-tab-active' : ''}`}
                    onClick={() => setActiveTab('api')}
                >
                    {__('API Settings', 'autoblogger')}
                </button>
                <button 
                    className={`nav-tab ${activeTab === 'prompts' ? 'nav-tab-active' : ''}`}
                    onClick={() => setActiveTab('prompts')}
                >
                    {__('Prompts', 'autoblogger')}
                </button>
                <button 
                    className={`nav-tab ${activeTab === 'content' ? 'nav-tab-active' : ''}`}
                    onClick={() => setActiveTab('content')}
                >
                    {__('Content Settings', 'autoblogger')}
                </button>
                <button 
                    className={`nav-tab ${activeTab === 'advanced' ? 'nav-tab-active' : ''}`}
                    onClick={() => setActiveTab('advanced')}
                >
                    {__('Advanced', 'autoblogger')}
                </button>
            </div>

            <form onSubmit={saveSettings} className="autoblogger-form">
                {activeTab === 'api' && (
                    <div className="tab-content">
                        <h3>{__('API Configuration', 'autoblogger')}</h3>
                        
                        <table className="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label htmlFor="api_provider">{__('AI Provider', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <select 
                                            id="api_provider"
                                            value={settings.api_provider}
                                            onChange={(e) => handleInputChange('api_provider', e.target.value)}
                                        >
                                            <option value="claude">Claude (Anthropic)</option>
                                            <option value="gemini">Gemini (Google)</option>
                                        </select>
                                        <p className="description">
                                            {__('Choose your AI provider', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label htmlFor="api_key">{__('API Key', 'autoblogger')} *</label>
                                    </th>
                                    <td>
                                        <input 
                                            type="password"
                                            id="api_key"
                                            className="regular-text"
                                            value={settings.api_key}
                                            onChange={(e) => handleInputChange('api_key', e.target.value)}
                                            placeholder={__('Enter your API key', 'autoblogger')}
                                            required
                                        />
                                        <p className="description">
                                            {settings.api_provider === 'claude' 
                                                ? __('Get your API key from https://console.anthropic.com/', 'autoblogger')
                                                : __('Get your API key from https://makersuite.google.com/app/apikey', 'autoblogger')
                                            }
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label htmlFor="api_model">{__('AI Model', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <select 
                                            id="api_model"
                                            value={settings.api_model}
                                            onChange={(e) => handleInputChange('api_model', e.target.value)}
                                        >
                                            {settings.api_provider === 'claude' ? (
                                                <>
                                                    <option value="claude-3-5-sonnet-20241022">Claude 3.5 Sonnet (Latest)</option>
                                                    <option value="claude-3-5-haiku-20241022">Claude 3.5 Haiku (Latest - Fast)</option>
                                                    <option value="claude-3-opus-20240229">Claude 3 Opus</option>
                                                    <option value="claude-3-sonnet-20240229">Claude 3 Sonnet</option>
                                                    <option value="claude-3-haiku-20240307">Claude 3 Haiku</option>
                                                </>
                                            ) : (
                                                <>
                                                    <option value="gemini-2.5-flash-latest">Gemini 2.5 Flash (Latest - Recommended)</option>
                                                    <option value="gemini-2.0-flash-exp">Gemini 2.0 Flash (Experimental - Free)</option>
                                                    <option value="gemini-1.5-pro-002">Gemini 1.5 Pro</option>
                                                    <option value="gemini-1.5-flash-002">Gemini 1.5 Flash</option>
                                                    <option value="gemini-1.5-pro">Gemini 1.5 Pro (Legacy)</option>
                                                    <option value="gemini-1.5-flash">Gemini 1.5 Flash (Legacy)</option>
                                                    <option value="gemini-pro">Gemini Pro (Legacy)</option>
                                                </>
                                            )}
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label htmlFor="daily_budget">{__('Daily Budget (USD)', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <input 
                                            type="number"
                                            id="daily_budget"
                                            step="0.01"
                                            min="0"
                                            value={settings.daily_budget}
                                            onChange={(e) => handleInputChange('daily_budget', parseFloat(e.target.value))}
                                        />
                                        <p className="description">
                                            {__('Maximum amount to spend per day on AI API calls', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label htmlFor="max_optimization_iterations">{__('Max SEO Optimization Iterations', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <input 
                                            type="number"
                                            id="max_optimization_iterations"
                                            min="1"
                                            max="5"
                                            value={settings.max_optimization_iterations}
                                            onChange={(e) => handleInputChange('max_optimization_iterations', parseInt(e.target.value))}
                                        />
                                        <p className="description">
                                            {__('Maximum number of times to retry SEO optimization (recommended: 2)', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                )}

                {activeTab === 'prompts' && (
                    <div className="tab-content">
                        <h3>{__('Prompt Templates', 'autoblogger')}</h3>
                        <p className="description" style={{ marginBottom: '20px' }}>
                            {__('Customize the AI prompts used for content generation. Use placeholders like {{keyword}}, {{knowledge_context}}, {{user_story}}.', 'autoblogger')}
                        </p>
                        
                        <div className="autoblogger-prompts">
                            <div className="prompt-section">
                                <h4>
                                    {__('Generate Outline', 'autoblogger')}
                                    <span 
                                        className="dashicons dashicons-info" 
                                        style={{ fontSize: '18px', marginLeft: '8px', color: '#2271b1', cursor: 'help' }}
                                        title=""
                                        onClick={(e) => {
                                            e.preventDefault();
                                            alert('Đây là Prompt để: Chỉ đạo AI lên cấu trúc khung sườn cho toàn bộ bài viết.\n\nHướng dẫn:\n\n• Hãy yêu cầu AI sắp xếp các thẻ H2, H3 theo trình tự logic (ví dụ: Định nghĩa → Ý nghĩa → Vận hạn → Kết luận).\n\n• Nhắc AI phân bổ từ khóa {{keyword}} vào các tiêu đề để đảm bảo điểm SEO ngay từ đầu.\n\n• Lưu ý: Prompt này chỉ sinh ra tiêu đề, không sinh ra nội dung chi tiết.');
                                        }}
                                    ></span>
                                </h4>
                                <textarea 
                                    rows="6"
                                    className="large-text code"
                                    value={prompts['generate-outline']}
                                    onChange={(e) => handlePromptChange('generate-outline', e.target.value)}
                                    placeholder={__('Enter prompt template for generating article outlines', 'autoblogger')}
                                />
                            </div>

                            <div className="prompt-section">
                                <h4>
                                    {__('Generate Draft', 'autoblogger')}
                                    <span 
                                        className="dashicons dashicons-info" 
                                        style={{ fontSize: '18px', marginLeft: '8px', color: '#2271b1', cursor: 'help' }}
                                        title=""
                                        onClick={(e) => {
                                            e.preventDefault();
                                            alert('Đây là Prompt để: Viết một bài viết hoàn chỉnh (hoặc phần Tóm tắt/Intro) trong một lần chạy.\n\nHướng dẫn:\n\n• Thường dùng cho các bài tin tức ngắn hoặc đoạn mở đầu. Không nên dùng cho bài luận giải chi tiết (vì dễ bị giới hạn độ dài).\n\n• Cần định nghĩa rõ Giọng văn (Tone & Voice) tại đây (ví dụ: Uyên bác, Cổ điển hay Hiện đại, Dễ hiểu).\n\n• Đừng quên yêu cầu AI thêm câu miễn trừ trách nhiệm (Disclaimer) ở cuối.');
                                        }}
                                    ></span>
                                </h4>
                                <textarea 
                                    rows="8"
                                    className="large-text code"
                                    value={prompts['generate-draft']}
                                    onChange={(e) => handlePromptChange('generate-draft', e.target.value)}
                                    placeholder={__('Enter prompt template for generating full article drafts', 'autoblogger')}
                                />
                            </div>

                            <div className="prompt-section">
                                <h4>
                                    {__('Generate Section', 'autoblogger')}
                                    <span 
                                        className="dashicons dashicons-info" 
                                        style={{ fontSize: '18px', marginLeft: '8px', color: '#2271b1', cursor: 'help' }}
                                        title=""
                                        onClick={(e) => {
                                            e.preventDefault();
                                            alert('Đây là Prompt để: Viết nội dung chi tiết cho từng thẻ H2/H3 cụ thể khi bạn click nút "Viết phần này" trong Editor.\n\nHướng dẫn:\n\n• Đây là nơi quyết định chất lượng bài viết. Hãy yêu cầu AI đóng vai "Chuyên gia" để phân tích sâu.\n\n• Bắt buộc phải giữ placeholder {{knowledge_context}} để AI nhận dữ liệu từ sách Tử Vi.\n\n• Nên yêu cầu AI chia nhỏ đoạn văn để dễ đọc trên điện thoại.');
                                        }}
                                    ></span>
                                </h4>
                                <textarea 
                                    rows="6"
                                    className="large-text code"
                                    value={prompts['generate-section']}
                                    onChange={(e) => handlePromptChange('generate-section', e.target.value)}
                                    placeholder={__('Enter prompt template for generating individual sections', 'autoblogger')}
                                />
                            </div>

                            <div className="prompt-section">
                                <h4>
                                    {__('Optimize Content (SEO)', 'autoblogger')}
                                    <span 
                                        className="dashicons dashicons-info" 
                                        style={{ fontSize: '18px', marginLeft: '8px', color: '#2271b1', cursor: 'help' }}
                                        title=""
                                        onClick={(e) => {
                                            e.preventDefault();
                                            alert('Đây là Prompt để: Sửa lại một đoạn văn bản cụ thể dựa trên các lỗi mà Rank Math phát hiện (ví dụ: thiếu từ khóa, câu quá dài).\n\nHướng dẫn:\n\n• Hãy chỉ đạo AI chèn từ khóa {{keyword}} một cách tự nhiên, tránh nhồi nhét (keyword stuffing) gây phản cảm.\n\n• Yêu cầu AI giữ nguyên ý nghĩa gốc của đoạn văn, chỉ thay đổi cách diễn đạt cho chuẩn SEO.');
                                        }}
                                    ></span>
                                </h4>
                                <textarea 
                                    rows="6"
                                    className="large-text code"
                                    value={prompts['optimize-content']}
                                    onChange={(e) => handlePromptChange('optimize-content', e.target.value)}
                                    placeholder={__('Enter prompt template for SEO optimization', 'autoblogger')}
                                />
                            </div>

                            <div className="prompt-section">
                                <h4>
                                    {__('Expand Text', 'autoblogger')}
                                    <span 
                                        className="dashicons dashicons-info" 
                                        style={{ fontSize: '18px', marginLeft: '8px', color: '#2271b1', cursor: 'help' }}
                                        title=""
                                        onClick={(e) => {
                                            e.preventDefault();
                                            alert('Đây là Prompt để: Viết thêm một đoạn giải thích sâu hoặc đưa ra ví dụ minh họa khi bạn bôi đen một câu phú/thuật ngữ khó hiểu.\n\nHướng dẫn:\n\n• Dùng để giải nghĩa các từ Hán Việt hoặc câu phú Nôm (ví dụ: "Phá Quân Thìn Tuất vi ngã ngạnh").\n\n• Hãy yêu cầu AI liên hệ thực tế (Ví dụ: Ứng dụng trong kinh doanh, tình cảm thời nay là gì?) để tăng tính hữu ích (Helpful Content).');
                                        }}
                                    ></span>
                                </h4>
                                <textarea 
                                    rows="6"
                                    className="large-text code"
                                    value={prompts['expand-text']}
                                    onChange={(e) => handlePromptChange('expand-text', e.target.value)}
                                    placeholder={__('Enter prompt template for expanding selected text', 'autoblogger')}
                                />
                            </div>

                            <p className="submit">
                                <button 
                                    type="button"
                                    className="button button-primary"
                                    onClick={savePrompts}
                                    disabled={savingPrompts}
                                >
                                    {savingPrompts ? __('Saving...', 'autoblogger') : __('Save Prompts', 'autoblogger')}
                                </button>
                            </p>
                        </div>
                    </div>
                )}

                {activeTab === 'content' && (
                    <div className="tab-content">
                        <h3>{__('Content Settings', 'autoblogger')}</h3>
                        
                        <table className="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label htmlFor="system_prompt">{__('Global System Prompt', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <textarea 
                                            id="system_prompt"
                                            rows="10"
                                            className="large-text code"
                                            value={settings.system_prompt}
                                            onChange={(e) => handleInputChange('system_prompt', e.target.value)}
                                        />
                                        <p className="description">
                                            {__('This system prompt defines the AI\'s role and behavior for ALL content generation. It sets global rules, tone, and safety guidelines that apply to every request.', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label htmlFor="disclaimer_text">{__('Disclaimer Text', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <textarea 
                                            id="disclaimer_text"
                                            rows="4"
                                            className="large-text"
                                            value={settings.disclaimer_text}
                                            onChange={(e) => handleInputChange('disclaimer_text', e.target.value)}
                                        />
                                        <p className="description">
                                            {__('This disclaimer will be automatically added to AI-generated content', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label>{__('Writing Personas', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <div className="autoblogger-list-manager">
                                            {(settings.personas || []).map((persona, index) => (
                                                <div key={index} className="list-item">
                                                    <input 
                                                        type="text"
                                                        value={persona.name || ''}
                                                        onChange={(e) => {
                                                            const newPersonas = [...(settings.personas || [])];
                                                            newPersonas[index] = { ...persona, name: e.target.value };
                                                            handleInputChange('personas', newPersonas);
                                                        }}
                                                        placeholder={__('Persona name (e.g., Academic)', 'autoblogger')}
                                                        style={{ width: '200px', marginRight: '10px' }}
                                                    />
                                                    <textarea
                                                        value={persona.description || ''}
                                                        onChange={(e) => {
                                                            const newPersonas = [...(settings.personas || [])];
                                                            newPersonas[index] = { ...persona, description: e.target.value };
                                                            handleInputChange('personas', newPersonas);
                                                        }}
                                                        placeholder={__('Description/instructions for this writing style', 'autoblogger')}
                                                        rows="2"
                                                        style={{ width: '400px', marginRight: '10px' }}
                                                    />
                                                    <button 
                                                        type="button"
                                                        className="button button-small button-link-delete"
                                                        onClick={() => {
                                                            const newPersonas = settings.personas.filter((_, i) => i !== index);
                                                            handleInputChange('personas', newPersonas);
                                                        }}
                                                    >
                                                        {__('Remove', 'autoblogger')}
                                                    </button>
                                                </div>
                                            ))}
                                            <button 
                                                type="button"
                                                className="button button-secondary"
                                                onClick={() => {
                                                    const newPersonas = [...(settings.personas || []), { name: '', description: '' }];
                                                    handleInputChange('personas', newPersonas);
                                                }}
                                            >
                                                {__('+ Add Persona', 'autoblogger')}
                                            </button>
                                        </div>
                                        <p className="description">
                                            {__('Define custom writing styles/tones for content generation', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label>{__('Negative Keywords', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <div className="autoblogger-list-manager">
                                            {(settings.negative_keywords || []).map((keyword, index) => (
                                                <div key={index} className="list-item">
                                                    <input 
                                                        type="text"
                                                        value={keyword}
                                                        onChange={(e) => {
                                                            const newKeywords = [...(settings.negative_keywords || [])];
                                                            newKeywords[index] = e.target.value;
                                                            handleInputChange('negative_keywords', newKeywords);
                                                        }}
                                                        placeholder={__('Enter dangerous/prohibited phrase', 'autoblogger')}
                                                        style={{ width: '400px', marginRight: '10px' }}
                                                    />
                                                    <button 
                                                        type="button"
                                                        className="button button-small button-link-delete"
                                                        onClick={() => {
                                                            const newKeywords = settings.negative_keywords.filter((_, i) => i !== index);
                                                            handleInputChange('negative_keywords', newKeywords);
                                                        }}
                                                    >
                                                        {__('Remove', 'autoblogger')}
                                                    </button>
                                                </div>
                                            ))}
                                            <button 
                                                type="button"
                                                className="button button-secondary"
                                                onClick={() => {
                                                    const newKeywords = [...(settings.negative_keywords || []), ''];
                                                    handleInputChange('negative_keywords', newKeywords);
                                                }}
                                            >
                                                {__('+ Add Keyword', 'autoblogger')}
                                            </button>
                                        </div>
                                        <p className="description">
                                            {__('Block dangerous phrases like "chắc chắn chết", "bỏ thuốc", etc. Content with these will be flagged.', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                )}

                {activeTab === 'advanced' && (
                    <div className="tab-content">
                        <h3>{__('Advanced Settings', 'autoblogger')}</h3>
                        
                        <table className="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        {__('Database Status', 'autoblogger')}
                                    </th>
                                    <td>
                                        <p className="description">
                                            {__('Plugin tables are installed and ready', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        {__('API Key Encryption', 'autoblogger')}
                                    </th>
                                    <td>
                                        <p className="description">
                                            ✅ {__('AES-256 encryption enabled', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                )}

                <p className="submit">
                    <button 
                        type="submit" 
                        className="button button-primary"
                        disabled={saving}
                    >
                        {saving ? __('Saving...', 'autoblogger') : __('Save Settings', 'autoblogger')}
                    </button>
                </p>
            </form>
        </div>
    );
};

export default SettingsPage;

