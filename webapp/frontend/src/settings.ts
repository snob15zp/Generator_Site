export const settings = {
    apiUrl: process.env.NODE_ENV === 'production' ? '/device/api' : 'http://localhost:8080/api'
}