/**
 * Cost Display Component
 * Shows estimated cost with color-coded warnings
 */

import { __ } from '@wordpress/i18n';
import './CostDisplay.scss';

const CostDisplay = ({ estimate, onConfirm, onCancel, showActions = false }) => {
    if (!estimate) {
        return null;
    }

    const { 
        totalCost, 
        warningLevel, 
        warningColor, 
        warningMessage,
        inputTokens,
        outputTokens,
        totalTokens
    } = estimate;

    const formattedCost = totalCost < 0.01 
        ? `$${totalCost.toFixed(4)}` 
        : `$${totalCost.toFixed(2)}`;

    return (
        <div className={`autoblogger-cost-display autoblogger-cost-${warningLevel}`}>
            <div className="cost-header">
                <span className="cost-icon" style={{ color: warningColor }}>
                    {warningLevel === 'green' && '✅'}
                    {warningLevel === 'yellow' && '⚠️'}
                    {warningLevel === 'red' && '🔴'}
                </span>
                <span className="cost-label">
                    {__('Ước tính chi phí:', 'autoblogger')}
                </span>
                <span className="cost-amount" style={{ color: warningColor }}>
                    {formattedCost}
                </span>
            </div>

            <div className="cost-message" style={{ color: warningColor }}>
                {warningMessage}
            </div>

            <div className="cost-details">
                <div className="cost-detail-row">
                    <span className="detail-label">
                        {__('Tokens:', 'autoblogger')}
                    </span>
                    <span className="detail-value">
                        {totalTokens.toLocaleString()} 
                        <small> ({inputTokens.toLocaleString()} in + {outputTokens.toLocaleString()} out)</small>
                    </span>
                </div>
            </div>

            {showActions && warningLevel === 'red' && (
                <div className="cost-actions">
                    <button 
                        className="button button-secondary" 
                        onClick={onCancel}
                    >
                        {__('Hủy', 'autoblogger')}
                    </button>
                    <button 
                        className="button button-primary" 
                        onClick={onConfirm}
                        style={{ backgroundColor: warningColor }}
                    >
                        {__('Tiếp tục', 'autoblogger')}
                    </button>
                </div>
            )}

            {showActions && warningLevel !== 'red' && (
                <div className="cost-actions">
                    <button 
                        className="button button-primary" 
                        onClick={onConfirm}
                    >
                        {__('Bắt đầu', 'autoblogger')}
                    </button>
                </div>
            )}
        </div>
    );
};

export default CostDisplay;

