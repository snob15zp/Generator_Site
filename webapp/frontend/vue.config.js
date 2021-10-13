module.exports = {
    publicPath: process.env.NODE_ENV === 'production' ? '/device/' : '/',
    transpileDependencies: ["vuetify", 'vuex-module-decorators'],
    devServer: {
        port: 8090,
        host: "0.0.0.0",

        watchOptions: {
            aggregateTimeout: 500,
            ignored: ['node_modules'],
            poll: 1000
        }
    }
};
