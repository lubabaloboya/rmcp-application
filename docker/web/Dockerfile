FROM node:20-alpine

WORKDIR /usr/src/app

# Install dependencies during image build so runtime startup is reliable.
COPY web/package*.json ./
RUN set -eux; \
    until npm ci --no-audit --no-fund; do \
      echo "npm ci failed, retrying in 5s"; \
      sleep 5; \
    done
RUN set -eux; \
    until npm install --no-audit --no-fund --save-dev @rollup/rollup-linux-x64-musl; do \
      echo "rollup optional dependency install failed, retrying in 5s"; \
      sleep 5; \
    done

COPY web/ ./

ENV CI=true
ENV NG_CLI_ANALYTICS=false

EXPOSE 4200

CMD ["npm", "run", "start", "--", "--host", "0.0.0.0", "--port", "4200"]
