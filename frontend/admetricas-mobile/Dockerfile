# Build Stage
FROM node:20 AS build-stage
WORKDIR /app
COPY package*.json ./
RUN npm install --legacy-peer-deps --ignore-scripts
COPY . .
RUN npx quasar prepare
RUN npm run build

# Production Stage
FROM nginx:alpine as production-stage
COPY --from=build-stage /app/dist/spa /usr/share/nginx/html
# Custom Nginx Config for SPA
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
