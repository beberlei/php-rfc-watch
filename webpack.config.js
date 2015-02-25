module.exports = {
    entry: './src/js/app.jsx',
    output: {
        filename: 'bundle.js',
        path: 'web/assets/',
        publicPath: '/assets'
    },
    module: {
        loaders: [
            { test: /\.jsx$/, loader: 'jsx-loader?insertPragma=React.DOM&harmony' },
            { test: /\.less$/, loader: "style-loader!css-loader!less-loader" },
            { test: /\.css$/, loader: 'style-loader!css-loader' },
        ]
    },
    externals: {
        'react': 'React'
    },
    resolve: {
        extensions: ['', '.js', '.jsx']
    }
}
