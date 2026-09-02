const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

const themeDir = path.resolve( __dirname, 'wp-content/themes/demo-child' );

module.exports = ( env, argv ) => {
	const isProduction = argv.mode === 'production';

	return {
		entry: {
			main: path.resolve( themeDir, 'src/js/main.js' ),
			style: path.resolve( themeDir, 'src/scss/main.scss' ),
		},
		output: {
			path: path.resolve( themeDir, 'assets/dist' ),
			filename: 'js/[name].js',
			clean: true,
		},
		devtool: isProduction ? false : 'source-map',
		module: {
			rules: [
				{
					test: /\.scss$/,
					use: [ MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader' ],
				},
			],
		},
		plugins: [
			new RemoveEmptyScriptsPlugin(),
			new MiniCssExtractPlugin( { filename: 'css/[name].css' } ),
		],
		optimization: {
			minimizer: [ new TerserPlugin(), new CssMinimizerPlugin() ],
		},
	};
};
