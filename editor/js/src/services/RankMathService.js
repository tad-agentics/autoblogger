/**
 * RankMath Integration Service
 * Three-tier fallback: Redux Store → DOM Parsing → Basic Checks
 */

class RankMathService {
    constructor() {
        this.integrationMethod = null;
    }

    /**
     * Get SEO analysis
     */
    async getAnalysis(keyword) {
        try {
            // Try Redux Store first
            const storeData = this.tryStore();
            if (storeData) {
                this.integrationMethod = 'store';
                return storeData;
            }
        } catch (error) {
            console.warn('RankMath store failed:', error);
        }

        try {
            // Try DOM parsing
            const domData = this.tryDOM(keyword);
            if (domData) {
                this.integrationMethod = 'dom';
                return domData;
            }
        } catch (error) {
            console.warn('RankMath DOM failed:', error);
        }

        // Fall back to basic checks
        this.integrationMethod = 'basic';
        return this.basicCheck(keyword);
    }

    /**
     * Try getting data from Redux Store
     */
    tryStore() {
        if (!window.wp || !window.wp.data) {
            return null;
        }

        const select = window.wp.data.select;
        const rankMathStore = select('rank-math');

        if (!rankMathStore) {
            return null;
        }

        const score = rankMathStore.getScore?.() || 0;
        const results = rankMathStore.getResults?.() || {};

        return {
            score,
            issues: this.extractIssues(results),
            method: 'store'
        };
    }

    /**
     * Try parsing from DOM
     */
    tryDOM(keyword) {
        const scoreElement = document.querySelector('.rank-math-score');
        if (!scoreElement) {
            return null;
        }

        const scoreText = scoreElement.textContent || '0';
        const score = parseInt(scoreText.match(/\d+/)?.[0] || '0');

        const issues = [];
        const issueElements = document.querySelectorAll('.rank-math-result.error, .rank-math-result.warning');
        
        issueElements.forEach(el => {
            const text = el.textContent || '';
            if (text) {
                issues.push(text.trim());
            }
        });

        return {
            score,
            issues,
            method: 'dom'
        };
    }

    /**
     * Basic SEO checks (fallback)
     */
    basicCheck(keyword) {
        const content = this.getEditorContent();
        const issues = [];

        // Check keyword density
        const keywordCount = (content.match(new RegExp(keyword, 'gi')) || []).length;
        const wordCount = content.split(/\s+/).length;
        const density = (keywordCount / wordCount) * 100;

        if (density < 0.5) {
            issues.push(`Keyword "${keyword}" appears too few times (${keywordCount})`);
        } else if (density > 2.5) {
            issues.push(`Keyword density too high (${density.toFixed(1)}%)`);
        }

        // Check content length
        if (wordCount < 300) {
            issues.push(`Content too short (${wordCount} words)`);
        }

        // Check headings
        const hasH2 = /<h2/i.test(content);
        if (!hasH2) {
            issues.push('No H2 headings found');
        }

        // Estimate score
        const score = Math.max(0, 100 - (issues.length * 20));

        return {
            score,
            issues,
            method: 'basic'
        };
    }

    /**
     * Extract issues from RankMath results
     */
    extractIssues(results) {
        const issues = [];

        Object.entries(results).forEach(([key, result]) => {
            if (result.status === 'fail' || result.status === 'warning') {
                issues.push(result.message || key);
            }
        });

        return issues;
    }

    /**
     * Get editor content
     */
    getEditorContent() {
        if (window.wp && window.wp.data) {
            const select = window.wp.data.select;
            const blocks = select('core/block-editor').getBlocks();
            return blocks.map(block => block.attributes.content || '').join(' ');
        }

        return document.querySelector('.editor-post-text-editor')?.value || '';
    }
}

export default RankMathService;

