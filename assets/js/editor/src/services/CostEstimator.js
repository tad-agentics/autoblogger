/**
 * Cost Estimation Service
 * Simple Vietnamese-optimized token and cost estimation
 */

class CostEstimator {
    constructor() {
        // Simple formula for Vietnamese: 1 word ≈ 1.3 tokens
        this.tokensPerWord = 1.3;
        
        // Get pricing from provider (defaults to Claude)
        this.inputCostPerMillion = 3.00;
        this.outputCostPerMillion = 15.00;
        
        // Color thresholds
        this.thresholds = {
            green: 0.1,   // < $0.1
            yellow: 0.5   // $0.1 - $0.5, > $0.5 = red
        };
    }

    /**
     * Estimate tokens from Vietnamese text
     * Simple formula: Count words × 1.3
     */
    estimateTokens(text) {
        // Count words (split by whitespace)
        const words = text.trim().split(/\s+/).filter(word => word.length > 0);
        const wordCount = words.length;
        
        // 1 word ≈ 1.3 tokens for Vietnamese
        return Math.ceil(wordCount * this.tokensPerWord);
    }

    /**
     * Estimate cost for operation
     */
    estimateCost(inputText, expectedOutputWords = 2000) {
        const inputTokens = this.estimateTokens(inputText);
        
        // Convert output words to tokens
        const outputTokens = Math.ceil(expectedOutputWords * this.tokensPerWord);

        const inputCost = (inputTokens / 1000000) * this.inputCostPerMillion;
        const outputCost = (outputTokens / 1000000) * this.outputCostPerMillion;
        const totalCost = inputCost + outputCost;

        return {
            inputTokens,
            outputTokens,
            totalTokens: inputTokens + outputTokens,
            inputCost: this.roundCost(inputCost),
            outputCost: this.roundCost(outputCost),
            totalCost: this.roundCost(totalCost),
            warningLevel: this.getWarningLevel(totalCost),
            warningColor: this.getWarningColor(totalCost),
            warningMessage: this.getWarningMessage(totalCost)
        };
    }

    /**
     * Get warning level based on cost
     */
    getWarningLevel(cost) {
        if (cost < this.thresholds.green) {
            return 'green';
        } else if (cost < this.thresholds.yellow) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    /**
     * Get warning color (hex)
     */
    getWarningColor(cost) {
        const level = this.getWarningLevel(cost);
        
        const colors = {
            green: '#46b450',   // WordPress success green
            yellow: '#ffb900',  // WordPress warning yellow
            red: '#dc3232'      // WordPress error red
        };
        
        return colors[level];
    }

    /**
     * Get warning message
     */
    getWarningMessage(cost) {
        const level = this.getWarningLevel(cost);
        
        const messages = {
            green: '✅ Chi phí thấp',
            yellow: '⚠️ Chi phí trung bình',
            red: '🔴 Chi phí cao - Xác nhận trước khi tiếp tục'
        };
        
        return messages[level];
    }

    /**
     * Round cost to 4 decimal places
     */
    roundCost(cost) {
        return Math.round(cost * 10000) / 10000;
    }

    /**
     * Format cost for display
     */
    formatCost(cost) {
        if (cost < 0.01) {
            return `$${cost.toFixed(4)}`;
        }
        return `$${cost.toFixed(2)}`;
    }

    /**
     * Get formatted cost with color
     */
    getFormattedCostWithColor(cost) {
        return {
            text: this.formatCost(cost),
            color: this.getWarningColor(cost),
            level: this.getWarningLevel(cost),
            message: this.getWarningMessage(cost)
        };
    }

    /**
     * Estimate cost for common operations
     */
    estimateOperationCost(operation, keyword = '') {
        const estimates = {
            'outline': {
                inputWords: 50,
                outputWords: 200,
                description: 'Tạo dàn ý'
            },
            'draft': {
                inputWords: 200,
                outputWords: 2000,
                description: 'Viết bài đầy đủ'
            },
            'section': {
                inputWords: 100,
                outputWords: 300,
                description: 'Viết một đoạn'
            },
            'optimize': {
                inputWords: 500,
                outputWords: 500,
                description: 'Tối ưu SEO'
            },
            'expand': {
                inputWords: 50,
                outputWords: 200,
                description: 'Mở rộng nội dung'
            }
        };

        const estimate = estimates[operation] || estimates['draft'];
        
        // Create sample input text
        const inputText = keyword + ' ' + 'context '.repeat(estimate.inputWords);
        
        return {
            ...this.estimateCost(inputText, estimate.outputWords),
            operation: operation,
            description: estimate.description
        };
    }

    /**
     * Update pricing based on provider
     */
    updatePricing(provider) {
        const pricing = {
            'claude': {
                input: 3.00,
                output: 15.00
            },
            'gemini': {
                input: 0.50,
                output: 1.50
            },
            'openai': {
                input: 10.00,
                output: 30.00
            }
        };

        const providerPricing = pricing[provider] || pricing['claude'];
        this.inputCostPerMillion = providerPricing.input;
        this.outputCostPerMillion = providerPricing.output;
    }
}

export default CostEstimator;

