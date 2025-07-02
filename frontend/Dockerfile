FROM node:22

WORKDIR /app

# Copia apenas os arquivos necessários para instalar as dependências
COPY package*.json ./

# Instala as dependências, incluindo o Vite
RUN npm install

# Copia o restante dos arquivos do projeto (incluindo vite.config.js, src/, etc.)
COPY . .

# Expõe a porta usada pelo Vite (por padrão 5173)
EXPOSE 5173

# Comando para iniciar a aplicação em modo dev
CMD ["npm", "run", "dev", "--", "--host"]
