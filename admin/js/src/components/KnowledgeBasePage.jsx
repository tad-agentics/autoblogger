/**
 * Knowledge Base Page Component
 * Manages knowledge base entries with CRUD operations
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const KnowledgeBasePage = () => {
    const [entries, setEntries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editingEntry, setEditingEntry] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [message, setMessage] = useState(null);
    
    const [formData, setFormData] = useState({
        keyword: '',
        content: '',
        metadata: '{}'
    });

    useEffect(() => {
        loadEntries();
    }, []);

    const loadEntries = async () => {
        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/knowledge',
                method: 'GET'
            });
            
            if (response.success) {
                setEntries(response.data);
            }
        } catch (error) {
            console.error('Failed to load entries:', error);
            setMessage({ type: 'error', text: __('Failed to load knowledge base', 'autoblogger') });
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setMessage(null);

        try {
            // Validate JSON
            JSON.parse(formData.metadata);
            
            const response = await apiFetch({
                path: editingEntry 
                    ? `/autoblogger/v1/knowledge/${editingEntry.id}`
                    : '/autoblogger/v1/knowledge',
                method: editingEntry ? 'PUT' : 'POST',
                data: formData
            });

            if (response.success) {
                setMessage({ 
                    type: 'success', 
                    text: editingEntry 
                        ? __('Entry updated successfully!', 'autoblogger')
                        : __('Entry created successfully!', 'autoblogger')
                });
                loadEntries();
                resetForm();
            }
        } catch (error) {
            console.error('Failed to save entry:', error);
            setMessage({ type: 'error', text: __('Failed to save entry. Check JSON format.', 'autoblogger') });
        }
    };

    const handleEdit = (entry) => {
        setEditingEntry(entry);
        setFormData({
            keyword: entry.keyword,
            content: typeof entry.content === 'string' ? entry.content : JSON.stringify(entry.content, null, 2),
            metadata: typeof entry.metadata === 'string' ? entry.metadata : JSON.stringify(entry.metadata, null, 2)
        });
        setShowForm(true);
    };

    const handleDelete = async (id) => {
        if (!confirm(__('Are you sure you want to delete this entry?', 'autoblogger'))) {
            return;
        }

        try {
            const response = await apiFetch({
                path: `/autoblogger/v1/knowledge/${id}`,
                method: 'DELETE'
            });

            if (response.success) {
                setMessage({ type: 'success', text: __('Entry deleted successfully!', 'autoblogger') });
                loadEntries();
            }
        } catch (error) {
            console.error('Failed to delete entry:', error);
            setMessage({ type: 'error', text: __('Failed to delete entry', 'autoblogger') });
        }
    };

    const resetForm = () => {
        setFormData({ keyword: '', content: '', metadata: '{}' });
        setEditingEntry(null);
        setShowForm(false);
    };

    const handleExport = () => {
        const dataStr = JSON.stringify(entries, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `autoblogger-knowledge-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);
        setMessage({ type: 'success', text: __('Knowledge base exported!', 'autoblogger') });
    };

    const handleImport = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = async (event) => {
            try {
                const importedData = JSON.parse(event.target.result);
                
                if (!Array.isArray(importedData)) {
                    setMessage({ type: 'error', text: __('Invalid JSON format', 'autoblogger') });
                    return;
                }

                let successCount = 0;
                let errorCount = 0;

                for (const entry of importedData) {
                    try {
                        await apiFetch({
                            path: '/autoblogger/v1/knowledge',
                            method: 'POST',
                            data: {
                                keyword: entry.keyword,
                                content: typeof entry.content === 'string' ? entry.content : JSON.stringify(entry.content),
                                metadata: typeof entry.metadata === 'string' ? entry.metadata : JSON.stringify(entry.metadata || {})
                            }
                        });
                        successCount++;
                    } catch (error) {
                        errorCount++;
                        console.error('Failed to import entry:', entry.keyword, error);
                    }
                }

                loadEntries();
                setMessage({ 
                    type: 'success', 
                    text: __('Import complete!', 'autoblogger') + ` ${successCount} ${__('success', 'autoblogger')}, ${errorCount} ${__('failed', 'autoblogger')}`
                });
            } catch (error) {
                console.error('Import failed:', error);
                setMessage({ type: 'error', text: __('Failed to parse JSON file', 'autoblogger') });
            }
        };
        reader.readAsText(file);
        
        // Reset file input
        e.target.value = '';
    };

    if (loading) {
        return <div className="autoblogger-loading">{__('Loading knowledge base...', 'autoblogger')}</div>;
    }

    return (
        <div className="autoblogger-knowledge">
            {message && (
                <div className={`notice notice-${message.type} is-dismissible`}>
                    <p>{message.text}</p>
                </div>
            )}

            <div className="autoblogger-header">
                <button 
                    className="button button-primary"
                    onClick={() => setShowForm(!showForm)}
                >
                    {showForm ? __('Cancel', 'autoblogger') : __('Add New Entry', 'autoblogger')}
                </button>
                <button 
                    className="button button-secondary"
                    onClick={handleExport}
                    style={{ marginLeft: '10px' }}
                >
                    {__('Export JSON', 'autoblogger')}
                </button>
                <label className="button button-secondary" style={{ marginLeft: '10px', cursor: 'pointer' }}>
                    {__('Import JSON', 'autoblogger')}
                    <input 
                        type="file"
                        accept=".json"
                        onChange={handleImport}
                        style={{ display: 'none' }}
                    />
                </label>
            </div>

            {showForm && (
                <div className="autoblogger-form-card">
                    <h3>{editingEntry ? __('Edit Entry', 'autoblogger') : __('Add New Entry', 'autoblogger')}</h3>
                    
                    <form onSubmit={handleSubmit}>
                        <table className="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <label htmlFor="keyword">{__('Keyword', 'autoblogger')} *</label>
                                    </th>
                                    <td>
                                        <input 
                                            type="text"
                                            id="keyword"
                                            className="regular-text"
                                            value={formData.keyword}
                                            onChange={(e) => setFormData({...formData, keyword: e.target.value})}
                                            required
                                        />
                                        <p className="description">
                                            {__('Main keyword for this entry (e.g., "Sao Phá Quân")', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label htmlFor="content">{__('Content', 'autoblogger')} *</label>
                                    </th>
                                    <td>
                                        <textarea 
                                            id="content"
                                            rows="10"
                                            className="large-text code"
                                            value={formData.content}
                                            onChange={(e) => setFormData({...formData, content: e.target.value})}
                                            placeholder={__('Enter content as text or JSON', 'autoblogger')}
                                            required
                                        />
                                        <p className="description">
                                            {__('Content for RAG context. Can be plain text or JSON.', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label htmlFor="metadata">{__('Metadata (JSON)', 'autoblogger')}</label>
                                    </th>
                                    <td>
                                        <textarea 
                                            id="metadata"
                                            rows="5"
                                            className="large-text code"
                                            value={formData.metadata}
                                            onChange={(e) => setFormData({...formData, metadata: e.target.value})}
                                            placeholder='{"source": "Book Name", "author": "Author Name"}'
                                        />
                                        <p className="description">
                                            {__('Additional metadata in JSON format (optional)', 'autoblogger')}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <p className="submit">
                            <button type="submit" className="button button-primary">
                                {editingEntry ? __('Update Entry', 'autoblogger') : __('Add Entry', 'autoblogger')}
                            </button>
                            <button 
                                type="button" 
                                className="button"
                                onClick={resetForm}
                                style={{ marginLeft: '10px' }}
                            >
                                {__('Cancel', 'autoblogger')}
                            </button>
                        </p>
                    </form>
                </div>
            )}

            <div className="autoblogger-table-wrapper">
                <h3>{__('Knowledge Base Entries', 'autoblogger')} ({entries.length})</h3>
                
                {entries.length === 0 ? (
                    <p className="description">
                        {__('No entries yet. Add your first entry to get started!', 'autoblogger')}
                    </p>
                ) : (
                    <table className="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style={{ width: '20%' }}>{__('Keyword', 'autoblogger')}</th>
                                <th style={{ width: '50%' }}>{__('Content Preview', 'autoblogger')}</th>
                                <th style={{ width: '15%' }}>{__('Created', 'autoblogger')}</th>
                                <th style={{ width: '15%' }}>{__('Actions', 'autoblogger')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {entries.map(entry => (
                                <tr key={entry.id}>
                                    <td><strong>{entry.keyword}</strong></td>
                                    <td>
                                        <code style={{ 
                                            display: 'block',
                                            maxHeight: '60px',
                                            overflow: 'hidden',
                                            textOverflow: 'ellipsis'
                                        }}>
                                            {typeof entry.content === 'string' 
                                                ? entry.content.substring(0, 100) + '...'
                                                : JSON.stringify(entry.content).substring(0, 100) + '...'
                                            }
                                        </code>
                                    </td>
                                    <td>{new Date(entry.created_at).toLocaleDateString()}</td>
                                    <td>
                                        <button 
                                            className="button button-small"
                                            onClick={() => handleEdit(entry)}
                                        >
                                            {__('Edit', 'autoblogger')}
                                        </button>
                                        {' '}
                                        <button 
                                            className="button button-small button-link-delete"
                                            onClick={() => handleDelete(entry.id)}
                                        >
                                            {__('Delete', 'autoblogger')}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
};

export default KnowledgeBasePage;

