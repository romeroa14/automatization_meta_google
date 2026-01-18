# Build Stage - Optimizado para Quasar
FROM node:20-alpine AS build-stage

WORKDIR /app

# Copiar TODO el proyecto primero (para que quasar prepare funcione)
COPY . .

# Install dependencies (ignorar scripts primero para evitar error de quasar prepare)
RUN npm install --legacy-peer-deps --ignore-scripts

# Ejecutar quasar prepare manualmente después de npm install
RUN ./node_modules/.bin/quasar prepare

# Build
RUN npm run build

# Production Stage
FROM nginx:alpine as production-stage

# Configuración optimizada de nginx
RUN cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }
    location / {
        try_files $uri $uri/ /index.html;
    }
}
EOF

COPY --from=build-stage /app/dist/spa /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
