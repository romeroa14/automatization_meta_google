# Build Stage - Optimizado con cache
FROM node:20-alpine AS build-stage

WORKDIR /app

# Copiar package primero para cache de npm
COPY package*.json ./

# Install dependencies con cache
RUN --mount=type=cache,target=/app/node_modules \
    npm install --legacy-peer-deps --ignore-scripts

# Copiar resto del código
COPY . .

# Preparar Quasar
RUN npx quasar prepare

# Build con cache
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

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }

    # SPA routing
    location / {
        try_files $uri $uri/ /index.html;
    }
}
EOF

# Copiar archivos compilados
COPY --from=build-stage /app/dist/spa /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
