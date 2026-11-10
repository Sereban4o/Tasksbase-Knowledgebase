# ── Stage 1: build the React SPA ─────────────────────────────────────────────
FROM node:20-alpine AS build

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json* ./
RUN npm install --legacy-peer-deps

COPY frontend/ ./

ARG VITE_REVERB_KEY=chat-key
ENV VITE_REVERB_KEY=$VITE_REVERB_KEY

RUN npm run build

# ── Stage 2: nginx serving SPA + reverse proxy to PHP-FPM and Reverb ─────────
FROM nginx:1.31-alpine

RUN mkdir -p /etc/nginx/snippets

COPY nginx/prod/default.conf /etc/nginx/conf.d/default.conf
COPY nginx/prod/laravel.conf /etc/nginx/snippets/laravel.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80
