FROM node:20-alpine

WORKDIR /app

# Install dependencies first (layer caching)
COPY package.json package-lock.json* ./

RUN npm install --legacy-peer-deps && \
    npm install --legacy-peer-deps \
      i18next@24.0.0 \
      react-i18next@15.0.0 \
      i18next-browser-languagedetector@8.0.0 \
      @tiptap/extension-underline@2.11.5 \
      @tiptap/extension-font-family@2.11.5 \
      @tiptap/extension-text-align@2.11.5 \
      @tiptap/extension-link@2.11.5 \
      @tiptap/extension-image@2.11.5 \
      @tiptap/extension-placeholder@2.11.5 \
      @tiptap/extension-blockquote@2.11.5 \
      @tiptap/extension-code-block@2.11.5 \
      @tiptap/extension-horizontal-rule@2.11.5 \
      @mui/x-data-grid@7.0.0

# Copy source files
COPY . .

EXPOSE 5173

CMD ["node", "node_modules/vite/bin/vite.js", "--host", "0.0.0.0", "--port", "5173"]
