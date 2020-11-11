'use strict';

const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const AssetsPlugin = require('assets-webpack-plugin');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const NODE_ENV = process.env.NODE_ENV || 'development';

const output_path = path.resolve(__dirname, 'public_html', 'js');

module.exports = {
  context: path.resolve(__dirname, 'src'),
  entry: {
    main: './js/main.js',
    subscribe: './js/subscribe.js',
  },

  output: {
    path: output_path,
    publicPath: "/js/",
    filename: '[name].[chunkhash].js',
    chunkFilename: "[id].[chunkhash].js",
  },

  watchOptions: {
    aggregateTimeout: 300
  },

  devtool: NODE_ENV === 'development' ? 'eval-sourcemap' : false,

  plugins: [
    new webpack.NoEmitOnErrorsPlugin(),
    new webpack.DefinePlugin({
      'process.env': {
        NODE_ENV: JSON.stringify(NODE_ENV)
      }
    }),
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',

    }),
    new CleanWebpackPlugin([
      output_path
    ]),
    new webpack.optimize.CommonsChunkPlugin({
      name: 'common',
      minChunks: 2,
      minSize: 4096,
    }),
    new ExtractTextPlugin({
      filename: 'styles.[md5:contenthash:hex:20].css',
      allChunks: true
    }),
    new AssetsPlugin({
      filename: 'assets.json',
      path: output_path
    }),
  ],

  resolve: {
    modules: ['node_modules'],
    extensions: ['.js', '.json']
  },

  resolveLoader: {
    modules: ['node_modules'],
    moduleExtensions: ['-loader'],
    extensions: ['.js', '.json']
  },

  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
        options: {
          loaders: {
            'scss': 'vue-style-loader!css-loader!sass-loader',
            'sass': 'vue-style-loader!css-loader!sass-loader?indentedSyntax',
            'js': 'babel?' + JSON.stringify({
              "presets": [
                ["env", {"forceAllTransforms": true}],
                // "stage-3"
              ],
              'plugins': [
                'transform-class-properties',
                'transform-object-rest-spread',
              ],
              "comments": false,
            })
          }
        }
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /[\/\\]node_modules[\/\\]/,
        query: {
          'presets': [
            ['env',
              {
                forceAllTransforms: true,
                useBuiltIns: true,
                include: [
                  "transform-es2015-arrow-functions",
                  "transform-es2015-classes",
                  "transform-es2015-computed-properties",
                  "transform-es2015-object-super",
                  "transform-es2015-parameters",
                ],
              }
            ],
            // "stage-3"
          ],
          'plugins': [
            'transform-class-properties',
            'transform-object-rest-spread',
          ],
          'comments': false,
        },
      },
      {
        test: /\.css$/,
        use: ExtractTextPlugin.extract({
          fallback: 'style-loader',
          use: [
            {
              loader: 'css-loader',
              options: {
                minimize: NODE_ENV !== 'development',
              }
            },
            {
              loader: 'resolve-url-loader'
            }
          ]
        })
      },
      {
        test: /\.scss$/,
        use: ExtractTextPlugin.extract({
          fallback: 'style-loader',
          use: [
            {
              loader: "css-loader",
              options: {
                minimize: NODE_ENV !== 'development',
              }
            },
            {
              loader: "resolve-url-loader",
            },
            {
              loader: "sass-loader",
              options: {
                sourceMap: NODE_ENV === 'development'
              }
            }
          ]
        })
      },
      {
        test: /\.(eot|svg|ttf|woff|woff2|png|jpg|gif)$/,
        loader: 'file-loader',
        include: /[\/\\]node_modules[\/\\]/,
        options: {
          name: file => {
            let reg = new RegExp(/node_modules\/(.+)$/);
            let name = reg.exec(file.replace(/\\/g, '/'));
            return name.length === 2 ? name[1] : '[path][name].[ext]';
          }
        }
      },
      {
        test: /\.(eot|svg|ttf|woff|woff2)$/,
        loader: 'file-loader',
        exclude: /[\/\\]node_modules[\/\\]/,
        options: {
          name: '[path][name].[ext]',
          context: 'fonts/'
        }
      },
      {
        test: /\.(png|jpg|gif|svg)$/,
        loader: 'file-loader',
        exclude: /[\/\\]node_modules[\/\\]/,
        options: {
          name: '[path][name].[ext]',
          context: 'images/'
        }
      },

    ]
  }
};
