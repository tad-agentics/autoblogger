/**
 * Editor Lock Service
 * Prevents WordPress autosave conflicts during AI generation
 * 
 * Problem: WordPress Heartbeat API autosaves every 15-60s
 * If AI is generating content (takes 1-2 minutes), autosave can:
 * - Save old version while AI is writing
 * - Create version conflicts
 * - Show "There is a newer version" warning
 * 
 * Solution: Lock editor during AI operations
 */

import { dispatch, select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

class EditorLockService {
    constructor() {
        this.isLocked = false;
        this.lockReason = '';
        this.originalHeartbeatSettings = null;
    }

    /**
     * Lock editor during AI generation
     * Prevents autosave, manual save, and publish
     */
    lock(reason = __('AI is generating content...', 'autoblogger')) {
        if (this.isLocked) {
            return; // Already locked
        }

        this.isLocked = true;
        this.lockReason = reason;

        // 1. Disable post saving
        dispatch('core/editor').lockPostSaving('autoblogger-ai-generation');

        // 2. Disable post autosaving
        dispatch('core/editor').lockPostAutosaving('autoblogger-ai-generation');

        // 3. Pause Heartbeat API (WordPress autosave mechanism)
        this.pauseHeartbeat();

        // 4. Show lock notice
        dispatch('core/notices').createNotice(
            'warning',
            reason,
            {
                id: 'autoblogger-editor-lock',
                isDismissible: false,
                actions: []
            }
        );

        // 5. Add visual indicator
        this.addLockIndicator();

        console.log('[AutoBlogger] Editor locked:', reason);
    }

    /**
     * Unlock editor after AI generation completes
     */
    unlock() {
        if (!this.isLocked) {
            return; // Not locked
        }

        this.isLocked = false;
        this.lockReason = '';

        // 1. Enable post saving
        dispatch('core/editor').unlockPostSaving('autoblogger-ai-generation');

        // 2. Enable post autosaving
        dispatch('core/editor').unlockPostAutosaving('autoblogger-ai-generation');

        // 3. Resume Heartbeat API
        this.resumeHeartbeat();

        // 4. Remove lock notice
        dispatch('core/notices').removeNotice('autoblogger-editor-lock');

        // 5. Remove visual indicator
        this.removeLockIndicator();

        // 6. Show success notice
        dispatch('core/notices').createNotice(
            'success',
            __('AI generation complete. You can now save or publish.', 'autoblogger'),
            {
                type: 'snackbar',
                isDismissible: true
            }
        );

        console.log('[AutoBlogger] Editor unlocked');
    }

    /**
     * Pause WordPress Heartbeat API
     * Prevents autosave during AI generation
     */
    pauseHeartbeat() {
        if (typeof window.wp !== 'undefined' && window.wp.heartbeat) {
            // Store original settings
            this.originalHeartbeatSettings = {
                interval: window.wp.heartbeat.interval(),
                paused: false
            };

            // Pause heartbeat
            window.wp.heartbeat.interval('fast-suspend');
            
            console.log('[AutoBlogger] Heartbeat paused');
        } else {
            console.warn('[AutoBlogger] Heartbeat API not available');
        }
    }

    /**
     * Resume WordPress Heartbeat API
     */
    resumeHeartbeat() {
        if (typeof window.wp !== 'undefined' && window.wp.heartbeat) {
            // Resume heartbeat with original interval
            if (this.originalHeartbeatSettings) {
                window.wp.heartbeat.interval(this.originalHeartbeatSettings.interval || 'standard');
            } else {
                window.wp.heartbeat.interval('standard');
            }

            console.log('[AutoBlogger] Heartbeat resumed');
        }
    }

    /**
     * Add visual lock indicator to editor
     */
    addLockIndicator() {
        // Check if indicator already exists
        if (document.querySelector('.autoblogger-lock-indicator')) {
            return;
        }

        const indicator = document.createElement('div');
        indicator.className = 'autoblogger-lock-indicator';
        indicator.innerHTML = `
            <div class="autoblogger-lock-content">
                <span class="dashicons dashicons-lock"></span>
                <span class="autoblogger-lock-text">${this.lockReason}</span>
                <span class="autoblogger-lock-spinner">
                    <span class="spinner is-active"></span>
                </span>
            </div>
        `;

        // Add to editor
        const editorHeader = document.querySelector('.edit-post-header');
        if (editorHeader) {
            editorHeader.appendChild(indicator);
        }
    }

    /**
     * Remove visual lock indicator
     */
    removeLockIndicator() {
        const indicator = document.querySelector('.autoblogger-lock-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Check if editor is locked
     */
    isEditorLocked() {
        return this.isLocked;
    }

    /**
     * Get lock reason
     */
    getLockReason() {
        return this.lockReason;
    }

    /**
     * Force unlock (emergency use only)
     */
    forceUnlock() {
        console.warn('[AutoBlogger] Force unlocking editor');
        this.unlock();
    }

    /**
     * Prevent user from leaving page during AI generation
     */
    enableBeforeUnloadWarning() {
        this.beforeUnloadHandler = (e) => {
            if (this.isLocked) {
                const message = __('AI is still generating content. Are you sure you want to leave?', 'autoblogger');
                e.preventDefault();
                e.returnValue = message;
                return message;
            }
        };

        window.addEventListener('beforeunload', this.beforeUnloadHandler);
    }

    /**
     * Remove beforeunload warning
     */
    disableBeforeUnloadWarning() {
        if (this.beforeUnloadHandler) {
            window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            this.beforeUnloadHandler = null;
        }
    }

    /**
     * Disable specific editor features during lock
     */
    disableEditorFeatures() {
        // Disable block inserter
        const inserterButton = document.querySelector('.edit-post-header-toolbar__inserter-toggle');
        if (inserterButton) {
            inserterButton.disabled = true;
            inserterButton.style.opacity = '0.5';
            inserterButton.style.cursor = 'not-allowed';
        }

        // Disable block mover
        const blockMovers = document.querySelectorAll('.block-editor-block-mover');
        blockMovers.forEach(mover => {
            mover.style.pointerEvents = 'none';
            mover.style.opacity = '0.5';
        });

        // Disable block toolbar
        const blockToolbar = document.querySelector('.block-editor-block-toolbar');
        if (blockToolbar) {
            blockToolbar.style.pointerEvents = 'none';
            blockToolbar.style.opacity = '0.5';
        }
    }

    /**
     * Re-enable editor features after unlock
     */
    enableEditorFeatures() {
        // Enable block inserter
        const inserterButton = document.querySelector('.edit-post-header-toolbar__inserter-toggle');
        if (inserterButton) {
            inserterButton.disabled = false;
            inserterButton.style.opacity = '1';
            inserterButton.style.cursor = 'pointer';
        }

        // Enable block mover
        const blockMovers = document.querySelectorAll('.block-editor-block-mover');
        blockMovers.forEach(mover => {
            mover.style.pointerEvents = 'auto';
            mover.style.opacity = '1';
        });

        // Enable block toolbar
        const blockToolbar = document.querySelector('.block-editor-block-toolbar');
        if (blockToolbar) {
            blockToolbar.style.pointerEvents = 'auto';
            blockToolbar.style.opacity = '1';
        }
    }

    /**
     * Complete lock workflow
     * Use this when starting AI generation
     */
    lockForAIGeneration(operationName = 'AI Generation') {
        const reason = __(`${operationName} in progress... Please wait.`, 'autoblogger');
        
        this.lock(reason);
        this.enableBeforeUnloadWarning();
        this.disableEditorFeatures();

        console.log(`[AutoBlogger] Editor fully locked for: ${operationName}`);
    }

    /**
     * Complete unlock workflow
     * Use this when AI generation completes or fails
     */
    unlockAfterAIGeneration() {
        this.unlock();
        this.disableBeforeUnloadWarning();
        this.enableEditorFeatures();

        console.log('[AutoBlogger] Editor fully unlocked');
    }
}

// Export singleton instance
const editorLockService = new EditorLockService();
export default editorLockService;

