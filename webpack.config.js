const path = require('path');
const {BundleAnalyzerPlugin} = require('webpack-bundle-analyzer');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const isProduction = process.env.NODE_ENV === 'production'

module.exports = {
    entry: './src/resources/index.js',
    resolve: {
      alias: {
        'vue$': 'vue/dist/vue.js'
      }
    },
    module: {
      rules: [
        {
          test: /\.vue$/,
          loader: 'vue-loader'
        },
        {
          test: /\.js$/,
          loader: 'babel-loader'
        },
        {
          test: /\.css$/,
          use: [
            'vue-style-loader',
            {
              loader: MiniCssExtractPlugin.loader,
              options: {}
            },
            'css-loader'
          ]
        }
      ]
    },
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: isProduction ? 'bundle.min.js' : 'bundle.js'
    },
    mode: isProduction ? 'production' : 'development',
    devtool: !isProduction && 'eval-source-map',
    plugins: [
      new VueLoaderPlugin(),
      new BundleAnalyzerPlugin({
        analyzerMode: 'static',
        openAnalyzer: false,
        reportFilename: '../webpack-bundle-report.html'
      }),
      new MiniCssExtractPlugin({
        filename: '[name].css',
        chunkFilename: '[id].css',
      }),
    ],
};