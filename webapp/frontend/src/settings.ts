const URL_MAP: Record<string, string> = {
    'production': '/device/api',
    'testing': '/api',
    'development': 'http://localhost:8080/api'
};
export const settings = {
    apiUrl: URL_MAP[process.env.NODE_ENV] ?? 'http://localhost:8080/api'
    //apiUrl: '/api'
}