FROM node:20-slim

WORKDIR /app

COPY package.json .
RUN npm install --omit=dev

COPY server.js .
COPY aws ./aws
COPY azure ./azure
COPY gcp ./gcp
COPY shared ./shared

EXPOSE 8004
CMD ["node", "server.js"]
