/**
 * Content Optimizer Service
 * Handles SEO optimization with chunked generation to avoid timeouts
 */

import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import editorLockService from './EditorLockService';

class ContentOptimizer {
    constructor() {
        this.maxIterations = window.autobloggerEditor?.config?.maxIterations || 2;
        this.scoreThreshold = window.autobloggerEditor?.config?.scoreThreshold || 80;
        this.chunkTimeout = 90000; // 90 seconds per chunk
    }

    /**
     * Generate content in chunks to avoid timeout
     * Instead of generating entire article at once, generate section by section
     */
    async generateDraftChunked(keyword, persona, humanStory, outline) {
        const sections = this.parseOutline(outline);
        const generatedSections = [];
        let totalCost = 0;

        // CRITICAL: Lock editor to prevent autosave conflicts
        editorLockService.lockForAIGeneration(__('Generating article', 'autoblogger'));

        try {
            // Generate introduction
            const intro = await this.generateSection({
                heading: __('Introduction', 'autoblogger'),
                keyword,
                persona,
                context: humanStory || '',
                isIntro: true
            });

            generatedSections.push(intro.content);
            totalCost += intro.cost.total_cost;

            // Generate each main section
            for (let i = 0; i < sections.length; i++) {
                const section = sections[i];
                
                // Get context from previous sections
                const previousContext = generatedSections.slice(-2).join('\n\n');

                const result = await this.generateSection({
                    heading: section.heading,
                    keyword,
                    persona,
                    context: previousContext,
                    isIntro: false
                });

                generatedSections.push(result.content);
                totalCost += result.cost.total_cost;

                // Show progress
                this.showProgress(i + 1, sections.length);
            }

            // Generate conclusion
            const conclusion = await this.generateSection({
                heading: __('Conclusion', 'autoblogger'),
                keyword,
                persona,
                context: generatedSections.slice(-2).join('\n\n'),
                isConclusion: true
            });

            generatedSections.push(conclusion.content);
            totalCost += conclusion.cost.total_cost;

            // Combine all sections
            const fullContent = generatedSections.join('\n\n');

            return {
                success: true,
                content: fullContent,
                totalCost,
                sectionsGenerated: sections.length + 2 // +intro +conclusion
            };

        } catch (error) {
            console.error('Chunked generation failed:', error);
            throw error;
        } finally {
            // CRITICAL: Always unlock editor, even if generation fails
            editorLockService.unlockAfterAIGeneration();
        }
    }

    /**
     * Generate a single section
     */
    async generateSection(params) {
        const { heading, keyword, persona, context, isIntro, isConclusion } = params;

        try {
            const response = await apiFetch({
                path: '/autoblogger/v1/generate/section',
                method: 'POST',
                data: {
                    heading,
                    keyword,
                    persona,
                    context,
                    is_intro: isIntro || false,
                    is_conclusion: isConclusion || false
                },
                timeout: this.chunkTimeout
            });

            return response;

        } catch (error) {
            // If timeout, retry once with shorter content
            if (error.message.includes('timeout')) {
                console.warn(`Section "${heading}" timed out, retrying with shorter length...`);
                
                const retryResponse = await apiFetch({
                    path: '/autoblogger/v1/generate/section',
                    method: 'POST',
                    data: {
                        heading,
                        keyword,
                        persona,
                        context: context.substring(0, 500), // Shorter context
                        target_length: 200, // Shorter output
                        is_intro: isIntro || false,
                        is_conclusion: isConclusion || false
                    },
                    timeout: this.chunkTimeout
                });

                return retryResponse;
            }

            throw error;
        }
    }

    /**
     * Parse outline into sections
     */
    parseOutline(outline) {
        if (!outline) {
            return [
                { heading: __('Main Content', 'autoblogger') }
            ];
        }

        const sections = [];
        const lines = outline.split('\n');

        lines.forEach(line => {
            // Match H2 headings (## or starting with numbers like "1.")
            const h2Match = line.match(/^##\s+(.+)$/) || line.match(/^\d+\.\s+(.+)$/);
            
            if (h2Match) {
                sections.push({
                    heading: h2Match[1].trim()
                });
            }
        });

        // If no sections found, create default
        if (sections.length === 0) {
            sections.push({ heading: __('Main Content', 'autoblogger') });
        }

        return sections;
    }

    /**
     * Optimize content for SEO (also chunked)
     */
    async optimizeContent(content, keyword, seoIssues, persona) {
        // Split content into blocks
        const blocks = this.splitIntoBlocks(content);
        const optimizedBlocks = [];
        let totalCost = 0;

        for (let i = 0; i < blocks.length; i++) {
            const block = blocks[i];

            // Only optimize blocks that have issues
            if (this.blockHasIssues(block, seoIssues)) {
                try {
                    const result = await apiFetch({
                        path: '/autoblogger/v1/optimize',
                        method: 'POST',
                        data: {
                            content: block.content,
                            keyword,
                            seo_issues: this.getRelevantIssues(block, seoIssues),
                            persona
                        },
                        timeout: this.chunkTimeout
                    });

                    optimizedBlocks.push(result.content);
                    totalCost += result.cost.total_cost;

                } catch (error) {
                    console.warn(`Block ${i} optimization failed, keeping original:`, error);
                    optimizedBlocks.push(block.content);
                }
            } else {
                // Keep original if no issues
                optimizedBlocks.push(block.content);
            }
        }

        return {
            success: true,
            content: optimizedBlocks.join('\n\n'),
            totalCost
        };
    }

    /**
     * Split content into manageable blocks
     */
    splitIntoBlocks(content) {
        const blocks = [];
        const paragraphs = content.split(/\n\n+/);
        let currentBlock = [];
        let currentLength = 0;
        const maxBlockLength = 1000; // chars per block

        paragraphs.forEach(para => {
            const paraLength = para.length;

            if (currentLength + paraLength > maxBlockLength && currentBlock.length > 0) {
                // Save current block
                blocks.push({
                    content: currentBlock.join('\n\n'),
                    length: currentLength
                });

                // Start new block
                currentBlock = [para];
                currentLength = paraLength;
            } else {
                currentBlock.push(para);
                currentLength += paraLength;
            }
        });

        // Add last block
        if (currentBlock.length > 0) {
            blocks.push({
                content: currentBlock.join('\n\n'),
                length: currentLength
            });
        }

        return blocks;
    }

    /**
     * Check if block has SEO issues
     */
    blockHasIssues(block, seoIssues) {
        if (!seoIssues || seoIssues.length === 0) {
            return false;
        }

        // Check if any issue mentions content in this block
        return seoIssues.some(issue => {
            const issueLower = issue.toLowerCase();
            return issueLower.includes('keyword') || 
                   issueLower.includes('heading') ||
                   issueLower.includes('density');
        });
    }

    /**
     * Get relevant issues for a block
     */
    getRelevantIssues(block, seoIssues) {
        return seoIssues.filter(issue => {
            const issueLower = issue.toLowerCase();
            const blockLower = block.content.toLowerCase();

            // Check if issue is relevant to this block
            return issueLower.includes('keyword') || 
                   issueLower.includes('heading') ||
                   (issueLower.includes('link') && blockLower.includes('http'));
        });
    }

    /**
     * Show progress notification
     */
    showProgress(current, total) {
        const percent = Math.round((current / total) * 100);
        
        // Dispatch WordPress notice
        if (window.wp && window.wp.data) {
            const { dispatch } = window.wp.data;
            
            dispatch('core/notices').createNotice(
                'info',
                `${__('Generating content...', 'autoblogger')} ${percent}% (${current}/${total})`,
                {
                    id: 'autoblogger-progress',
                    isDismissible: false
                }
            );
        }
    }

    /**
     * Run optimization loop with iteration limit
     */
    async optimizeWithLoop(content, keyword, rankMathService, persona) {
        let currentContent = content;
        let iteration = 0;
        let currentScore = 0;

        // CRITICAL: Lock editor during optimization
        editorLockService.lockForAIGeneration(__('Optimizing content for SEO', 'autoblogger'));

        try {
            while (iteration < this.maxIterations) {
            // Get SEO analysis
            const analysis = await rankMathService.getAnalysis(keyword);
            currentScore = analysis.score;

            // Check if we've reached threshold
            if (currentScore >= this.scoreThreshold) {
                console.log(`SEO score ${currentScore} reached threshold ${this.scoreThreshold}`);
                break;
            }

            // Check if there are issues to fix
            if (!analysis.issues || analysis.issues.length === 0) {
                console.log('No SEO issues found');
                break;
            }

            // Ask user for confirmation on first iteration
            if (iteration === 0) {
                const confirmed = await this.confirmOptimization(analysis.issues);
                if (!confirmed) {
                    break;
                }
            }

            // Optimize content (chunked)
            console.log(`Optimization iteration ${iteration + 1}/${this.maxIterations}`);
            
            const result = await this.optimizeContent(
                currentContent,
                keyword,
                analysis.issues,
                persona
            );

            currentContent = result.content;
            iteration++;

            // Small delay to let RankMath re-analyze
            await this.sleep(2000);
            }

            return {
                content: currentContent,
                finalScore: currentScore,
                iterations: iteration
            };

        } finally {
            // CRITICAL: Always unlock editor
            editorLockService.unlockAfterAIGeneration();
        }
    }

    /**
     * Confirm optimization with user
     */
    async confirmOptimization(issues) {
        const message = `${__('AutoBlogger will optimize your content to fix these SEO issues:', 'autoblogger')}\n\n` +
                       issues.map(issue => `• ${issue}`).join('\n') +
                       `\n\n${__('This may take a few minutes. Continue?', 'autoblogger')}`;

        return window.confirm(message);
    }

    /**
     * Sleep helper
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Estimate total time for generation
     */
    estimateGenerationTime(outline) {
        const sections = this.parseOutline(outline);
        const totalSections = sections.length + 2; // +intro +conclusion
        const avgTimePerSection = 15; // seconds
        
        return totalSections * avgTimePerSection;
    }

    /**
     * Format time for display
     */
    formatTime(seconds) {
        if (seconds < 60) {
            return `${seconds} ${__('seconds', 'autoblogger')}`;
        }
        
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        return `${minutes} ${__('minutes', 'autoblogger')} ${remainingSeconds} ${__('seconds', 'autoblogger')}`;
    }
}

export default ContentOptimizer;

