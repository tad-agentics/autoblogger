const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'editor': path.resolve(process.cwd(), 'assets/js/editor/src', 'index.js'),
        'admin': path.resolve(process.cwd(), 'assets/js/admin/src', 'index.js'),
        'disclaimer-block': path.resolve(process.cwd(), 'blocks/disclaimer-block', 'index.js'),
        'expert-note-block': path.resolve(process.cwd(), 'blocks/expert-note-block', 'index.js')
    },
    output: {
        filename: '[name].js',
        path: path.resolve(process.cwd(), 'build')
    }
};

