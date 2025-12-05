const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'editor': path.resolve(process.cwd(), 'editor/js/src', 'index.js'),
        'admin': path.resolve(process.cwd(), 'admin/js/src', 'index.js'),
        'disclaimer-block': path.resolve(process.cwd(), 'includes/blocks/disclaimer-block', 'index.js'),
        'expert-note-block': path.resolve(process.cwd(), 'includes/blocks/expert-note-block', 'index.js')
    },
    output: {
        filename: '[name].js',
        path: path.resolve(process.cwd(), 'build')
    }
};

