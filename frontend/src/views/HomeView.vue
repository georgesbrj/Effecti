<template>
  <div class="licitation-list">
    <h1>Licitações Públicas</h1>

    <!-- Filtros -->
    <div class="filters">
      <div class="filter-group">
        <label for="uasg">Código UASG:</label>
        <input type="text" id="uasg" v-model="filters.uasg" placeholder="Ex: 12345" />
      </div>

      <div class="filter-group">
        <label for="biddingNumber">Número do Pregão:</label>
        <input type="text" id="biddingNumber" v-model="filters.biddingNumber" placeholder="Ex: 20230001" />
      </div>

      <button @click="fetchLicitations">Aplicar Filtros</button>
      <button @click="resetFilters">Limpar Filtros</button>
    </div>

    <!-- Listagem -->
    <div v-if="loading" class="loading">Carregando licitações...</div>

    <div v-else-if="licitations.length === 0" class="no-results">
      Nenhuma licitação encontrada com os filtros aplicados.
    </div>

    <ul v-else class="licitation-items">
      <li v-for="licitation in licitations" :key="licitation.id" :class="{ 'situacao': licitation.situacao }">
        <div class="licitation-header">
          <h2>{{ licitation.title }}</h2>
          <span class="read-status" :class="licitation.situacao == 1 ? 'read-true' : 'read-false'"
            @click.stop="toggleReadStatus(licitation)">
            {{ licitation.situacao == 1 ? "lido" : "Não lido" }}
          </span>
        </div>

        <p><strong>UASG:</strong> {{ licitation.uasg_codigo }}</p>
        <p><strong>Número do Pregão:</strong> {{ licitation.numero }}</p>
        <p><strong>Lei:</strong> {{ licitation.lei }}</p>
        <p><strong>Objeto:</strong> {{ licitation.objeto }}</p>
        <p><strong>Data:</strong> {{ formatDate(licitation.data_abertura) }}</p>
      </li>
    </ul>

    <!-- Paginação -->
    <div class="pagination" v-if="totalPages > 1">
      <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
        Anterior
      </button>

      <span v-for="page in pageRange" :key="page" @click="changePage(page)" :class="{ 'active': page === currentPage }">
        {{ page }}
      </span>

      <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
        Próxima
      </button>
    </div>

    <div class="pagination-info">
      Página {{ currentPage }} de {{ totalPages }} - Total: {{ pagination.totalItems }} itens
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      licitations: [],
      loading: false,
      filters: {
        uasg: '',
        biddingNumber: ''
      },
      pagination: {
        currentPage: 1,
        itemsPerPage: 10,
        totalItems: 0
      }
    };
  },
  computed: {
    currentPage() {
      return this.pagination.currentPage;
    },
    totalPages() {
      return Math.ceil(this.pagination.totalItems / this.pagination.itemsPerPage);
    },
    pageRange() {
      const range = [];
      const start = Math.max(1, this.currentPage - 2);
      const end = Math.min(this.totalPages, start + 4);

      for (let i = start; i <= end; i++) {
        range.push(i);
      }
      return range;
    }
  },
  mounted() {
    this.fetchLicitations();
  },
  methods: {
    async fetchLicitations() {
      this.loading = true;
      try {
        const params = {
          uasg_codigo: this.filters.uasg || undefined,
          numero: this.filters.biddingNumber || undefined,
          page: this.currentPage,
          limit: this.pagination.itemsPerPage
        };

        const response = await axios.get('http://localhost:8000/api/licitacoes', {
          params,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });

        this.licitations = response.data.data;

        this.licitations = this.licitations.map(item => ({
          ...item,
          situacao: Number(item.situacao)
        }));

        this.pagination.totalItems = response.data.pagination.total;

      } catch (error) {
        console.error('Erro ao buscar licitações:', {
          message: error.message,
          response: error.response?.data,
          status: error.response?.status
        });
        alert('Erro ao carregar licitações. Verifique o console para detalhes.');
      } finally {
        this.loading = false;
      }
    },
    resetFilters() {
      this.filters = {
        uasg: '',
        biddingNumber: ''
      };
      this.pagination.currentPage = 1;
      this.fetchLicitations();
    },
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.pagination.currentPage = page;
        this.fetchLicitations();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    },
    async toggleReadStatus(licitation) {
      try {
        const novoStatus = licitation.situacao === 1 ? 0 : 1;

        // Envia PATCH
        await axios.patch(
          `http://localhost:8000/api/licitacoes/${licitation.id}`,
          { situacao: novoStatus },
          {
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          }
        );

        // Atualiza o item na lista usando index
        const index = this.licitations.findIndex(l => l.id === licitation.id);
        if (index !== -1) {
          this.licitations[index].situacao = novoStatus;
        }

        console.log("Status atualizado com sucesso:", licitation.id, novoStatus);

      } catch (error) {
        console.error("Erro ao atualizar status:", error);
        alert("Falha ao atualizar a situação. Tente novamente.");
      }
    },

    isRead(situacao) {
      return Number(situacao) === 1;
    },



    formatDate(dateString) {
      const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
      return new Date(dateString).toLocaleDateString('pt-BR', options);
    }
  }
};
</script>

<style scoped>
.licitation-list {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.filters {
  background-color: #f5f5f5;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 200px;
}

label {
  font-weight: bold;
  margin-bottom: 5px;
}

input {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

button {
  padding: 8px 15px;
  background-color: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  align-self: flex-end;
}

button:hover {
  background-color: #2980b9;
}

.licitation-items {
  list-style: none;
  padding: 0;
}

.licitation-items li {
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  transition: all 0.3s ease;
}

.licitation-items li:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.licitation-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}



.read-status {
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 0.8em;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.3s ease;
}

.read-true {
  background-color: #2ecc71;
  /* Verde para "Lida"  2ecc71 */
  color: white;
}

.read-false {
  background-color: #e74c3c;
  /* Vermelho para "Não lida" */
  color: white;
}

.loading,
.no-results {
  text-align: center;
  padding: 20px;
  font-size: 1.2em;
  color: #7f8c8d;
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 20px;
}

.pagination button {
  padding: 5px 10px;
}

.pagination span {
  padding: 5px 10px;
  cursor: pointer;
}

.pagination span.active {
  font-weight: bold;
  color: #3498db;
}

.pagination-info {
  text-align: center;
  margin-top: 10px;
  color: #7f8c8d;
}
</style>