const defaultConfig = require('@wordpress/scripts/config/webpack.config');

// Let @wordpress/scripts handle the build configuration
// The output paths are controlled by package.json scripts
module.exports = {
    ...defaultConfig
};

