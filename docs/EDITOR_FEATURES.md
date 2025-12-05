# Editor Features Guide

Complete guide to Gutenberg editor features in AutoBlogger.

## 📚 Features Overview

| Feature | Purpose | Status |
|---------|---------|--------|
| Editor Lock Service | Prevent autosave conflicts | ✅ Implemented |
| Heartbeat API Control | Pause autosave during AI generation | ✅ Implemented |
| Cost Display | Show AI cost estimates | ✅ Implemented |
| Progress Indicators | Show generation progress | ✅ Implemented |
| Content Optimizer | Chunked generation & optimization | ✅ Implemented |

---

## 1. Editor Lock Service

### Purpose
Prevents WordPress autosave conflicts during AI generation (which can take 1-2 minutes).

### The Problem
```
0s:  User clicks "Generate"
15s: WordPress autosaves (old content) ❌
30s: WordPress autosaves (old content) ❌
45s: WordPress autosaves (old content) ❌
90s: AI finishes → "There is a newer version" warning ❌
```

### The Solution
```javascript
import editorLockService from './EditorLockService';

// Before AI generation
editorLockService.lockForAIGeneration('Generating article');

try {
    await generateContent();
} finally {
    // Always unlock
    editorLockService.unlockAfterAIGeneration();
}
```

### What It Does

**6-Layer Protection:**
1. **Disable Post Saving** - `lockPostSaving()`
2. **Disable Post Autosaving** - `lockPostAutosaving()`
3. **Pause Heartbeat API** - `heartbeat.interval('fast-suspend')`
4. **Visual Lock Indicator** - Yellow banner at top
5. **Beforeunload Warning** - Prevent accidental page close
6. **Disable Editor Features** - Prevent content modification

### Visual Feedback

**Lock Indicator:**
```
┌─────────────────────────────────────────────────┐
│ 🔒 AI is generating content... Please wait. ⏳  │
└─────────────────────────────────────────────────┘
```

**Disabled Features:**
- ❌ Cannot add blocks
- ❌ Cannot move blocks
- ❌ Cannot edit blocks
- ❌ Cannot save manually
- ❌ Cannot publish

### API

```javascript
// Lock editor
editorLockService.lock('Custom reason');

// Unlock editor
editorLockService.unlock();

// Complete lock workflow
editorLockService.lockForAIGeneration('Operation name');
editorLockService.unlockAfterAIGeneration();

// Check lock state
if (editorLockService.isEditorLocked()) {
    console.log('Locked:', editorLockService.getLockReason());
}

// Force unlock (emergency)
editorLockService.forceUnlock();
```

### Results
- ✅ No autosave conflicts
- ✅ No version warnings
- ✅ Clean content insertion
- ✅ Better UX

**File:** `editor/js/src/services/EditorLockService.js`

---

## 2. Heartbeat API Control

### Purpose
Pauses WordPress Heartbeat API (autosave mechanism) during AI operations.

### Implementation

**Pause Heartbeat:**
```javascript
pauseHeartbeat() {
    if (window.wp && window.wp.heartbeat) {
        // Store original settings
        this.originalHeartbeatSettings = {
            interval: window.wp.heartbeat.interval()
        };
        
        // Pause heartbeat
        window.wp.heartbeat.interval('fast-suspend');
    }
}
```

**Resume Heartbeat:**
```javascript
resumeHeartbeat() {
    if (window.wp && window.wp.heartbeat) {
        // Resume with original interval
        window.wp.heartbeat.interval(
            this.originalHeartbeatSettings?.interval || 'standard'
        );
    }
}
```

### Heartbeat Intervals

| Interval | Frequency | Use Case |
|----------|-----------|----------|
| `standard` | 60 seconds | Normal editing |
| `fast` | 15 seconds | Active editing |
| `fast-suspend` | Paused | AI generation ✅ |

### Results
- ✅ No autosave during AI generation
- ✅ No "Saving draft..." notifications
- ✅ No version conflicts

**File:** `editor/js/src/services/EditorLockService.js`

---

## 3. Cost Display Component

### Purpose
Shows real-time cost estimates for AI operations with color-coded warnings.

### Implementation

```jsx
import CostDisplay from './components/CostDisplay';

<CostDisplay
    estimate={costEstimate}
    isGenerating={isGenerating}
    onConfirm={handleConfirm}
    onCancel={handleCancel}
    showActions={true}
/>
```

### Cost Estimate Object

```javascript
{
    inputTokens: 1500,
    outputTokens: 2000,
    totalCost: 0.0525,
    warningLevel: 'green', // 'green', 'yellow', 'red'
    warningColor: '#46b450',
    warningMessage: '✅ Chi phí thấp'
}
```

### Warning Levels

| Level | Cost Range | Color | Message |
|-------|-----------|-------|---------|
| **Green** | < $0.10 | #46b450 | ✅ Chi phí thấp |
| **Yellow** | $0.10 - $0.50 | #ffb900 | ⚠️ Chi phí trung bình |
| **Red** | > $0.50 | #dc3232 | 🔴 Chi phí cao - Cần xác nhận |

### Visual Example

```
┌─────────────────────────────────────────┐
│ ✅ Ước tính chi phí: $0.0525            │
│ ✅ Chi phí thấp                         │
│ Tokens: 3,500 (1,500 in + 2,000 out)   │
│ [Bắt đầu]                               │
└─────────────────────────────────────────┘
```

### Results
- ✅ Users know cost before generating
- ✅ Color-coded warnings
- ✅ Can cancel expensive operations
- ✅ Budget control

**Files:** `editor/js/src/components/CostDisplay.jsx`, `editor/js/src/services/CostEstimator.js`

---

## 4. Progress Indicators

### Purpose
Show users what's happening during long AI operations.

### Implementation

**Simple Progress:**
```javascript
dispatch(noticesStore).createNotice(
    'info',
    'Generating content... Please wait.',
    {
        id: 'autoblogger-progress',
        isDismissible: false
    }
);
```

**Progress with Percentage:**
```javascript
const percent = Math.round((current / total) * 100);

dispatch(noticesStore).createNotice(
    'info',
    `Generating content... ${percent}% (${current}/${total})`,
    {
        id: 'autoblogger-progress',
        isDismissible: false
    }
);
```

**Progress with Steps:**
```javascript
dispatch(noticesStore).createNotice(
    'info',
    `Generating section "${heading}" (${i+1}/${total})...`,
    {
        id: 'autoblogger-progress',
        isDismissible: false
    }
);
```

### Results
- ✅ Users know operation is in progress
- ✅ Less likely to refresh page
- ✅ Better perceived performance
- ✅ Professional UX

**File:** `editor/js/src/services/ContentOptimizer.js`

---

## 5. Content Optimizer Service

### Purpose
Handles AI content generation and optimization with chunked processing.

### Features

**Chunked Generation:**
```javascript
async generateDraftChunked(keyword, persona, humanStory, outline) {
    // Lock editor
    editorLockService.lockForAIGeneration('Generating article');
    
    try {
        // Generate intro
        const intro = await this.generateSection(..., isIntro=true);
        
        // Generate sections
        for (let heading of outline) {
            const section = await this.generateSection(...);
        }
        
        // Generate conclusion
        const conclusion = await this.generateSection(..., isConclusion=true);
        
        return fullContent;
    } finally {
        // Always unlock
        editorLockService.unlockAfterAIGeneration();
    }
}
```

**Why Chunked?**
- ✅ Prevents PHP timeouts (each chunk < 120s)
- ✅ Shows progress to user
- ✅ Can resume if one chunk fails
- ✅ Better error handling

**Optimization Loop:**
```javascript
async optimizeWithLoop(content, keyword, rankMathService, persona) {
    // Lock editor
    editorLockService.lockForAIGeneration('Optimizing content');
    
    try {
        let iteration = 0;
        
        while (iteration < maxIterations) {
            // Get SEO analysis
            const analysis = await rankMathService.getAnalysis(keyword);
            
            if (analysis.score >= threshold) {
                break; // Good enough!
            }
            
            // Optimize content
            const result = await this.optimizeContent(...);
            
            iteration++;
        }
        
        return result;
    } finally {
        // Always unlock
        editorLockService.unlockAfterAIGeneration();
    }
}
```

**Why Loop Limiter?**
- ✅ Prevents infinite loops
- ✅ Controls API costs
- ✅ Max 2 iterations
- ✅ User confirmation for expensive operations

### Results
- ✅ No timeouts
- ✅ Better progress tracking
- ✅ Controlled costs
- ✅ Better error recovery

**File:** `editor/js/src/services/ContentOptimizer.js`

---

## 6. RankMath Integration

### Purpose
Three-tier fallback system for reading RankMath SEO scores.

### Implementation

**Tier 1: Redux Store (Preferred)**
```javascript
tryStore() {
    if (window.wp && window.wp.data) {
        const rankMathData = window.wp.data.select('rank-math');
        if (rankMathData) {
            return {
                score: rankMathData.getScore(),
                issues: rankMathData.getIssues()
            };
        }
    }
    return null;
}
```

**Tier 2: DOM Parsing (Fallback)**
```javascript
tryDOM(keyword) {
    const scoreElement = document.querySelector('.rank-math-score');
    if (scoreElement) {
        const score = parseInt(scoreElement.textContent);
        const issues = this.parseIssuesFromDOM();
        return { score, issues };
    }
    return null;
}
```

**Tier 3: Basic Checks (Last Resort)**
```javascript
basicCheck(keyword) {
    const content = this.getEditorContent();
    
    // Basic SEO checks
    const hasKeywordInTitle = this.checkTitle(keyword);
    const keywordDensity = this.calculateDensity(content, keyword);
    const hasMetaDescription = this.checkMetaDescription();
    
    return {
        score: this.calculateBasicScore(...),
        issues: this.getBasicIssues(...)
    };
}
```

### Why Three Tiers?
- ✅ Resilient to RankMath updates
- ✅ Always provides some feedback
- ✅ Graceful degradation
- ✅ Better UX

**File:** `editor/js/src/services/RankMathService.js`

---

## 📚 Usage Examples

### Example 1: Generate Article

```javascript
import ContentOptimizer from './services/ContentOptimizer';
import editorLockService from './services/EditorLockService';

const optimizer = new ContentOptimizer();

// User clicks "Generate" button
async function handleGenerate() {
    const keyword = 'Sao Phá Quân';
    const persona = 'Academic';
    const outline = ['Introduction', 'Meaning', 'Characteristics', 'Conclusion'];
    
    try {
        const result = await optimizer.generateDraftChunked(
            keyword,
            persona,
            '',
            outline
        );
        
        // Insert content into editor
        insertContent(result.content);
        
        // Show success
        showSuccess('Article generated successfully!');
        
    } catch (error) {
        showError('Generation failed: ' + error.message);
    }
}
```

### Example 2: Optimize for SEO

```javascript
import RankMathService from './services/RankMathService';

const rankMath = new RankMathService();
const optimizer = new ContentOptimizer();

// User clicks "Optimize" button
async function handleOptimize() {
    const content = getEditorContent();
    const keyword = 'Sao Phá Quân';
    const persona = 'Academic';
    
    try {
        const result = await optimizer.optimizeWithLoop(
            content,
            keyword,
            rankMath,
            persona
        );
        
        // Update content
        updateEditorContent(result.content);
        
        // Show final score
        showSuccess(`Optimized! Final score: ${result.finalScore}/100`);
        
    } catch (error) {
        showError('Optimization failed: ' + error.message);
    }
}
```

### Example 3: Show Cost Estimate

```javascript
import CostEstimator from './services/CostEstimator';
import CostDisplay from './components/CostDisplay';

const estimator = new CostEstimator();

// Before generation
function showCostEstimate() {
    const prompt = buildPrompt(keyword, context);
    const estimatedOutputWords = 2000;
    
    const estimate = estimator.estimateCost(prompt, estimatedOutputWords);
    
    // Show cost display
    ReactDOM.render(
        <CostDisplay
            estimate={estimate}
            onConfirm={handleGenerate}
            onCancel={handleCancel}
            showActions={true}
        />,
        document.getElementById('cost-display-root')
    );
}
```

---

## ✅ Best Practices

### Editor Locking
- ✅ Always lock before AI operations
- ✅ Always unlock in `finally` block
- ✅ Use descriptive lock reasons
- ✅ Show progress indicators

### Progress Indicators
- ✅ Show immediately when operation starts
- ✅ Update with percentage or steps
- ✅ Remove when operation completes
- ✅ Make non-dismissible during operation

### Cost Display
- ✅ Show before expensive operations
- ✅ Require confirmation for red warnings
- ✅ Update pricing dynamically
- ✅ Show token breakdown

### Content Optimizer
- ✅ Use chunked generation for long content
- ✅ Limit optimization iterations (max 2)
- ✅ Show progress for each chunk
- ✅ Handle errors gracefully

### RankMath Integration
- ✅ Try store first
- ✅ Fall back to DOM parsing
- ✅ Provide basic checks as last resort
- ✅ Log which method succeeded

---

## 📚 Related Documentation

- [Performance](PERFORMANCE.md) - Performance optimizations
- [Architecture](ARCHITECTURE.md) - System architecture
- [AI Providers](AI_PROVIDERS.md) - AI provider system

---

**All editor features are production-ready!** ✨🚀

