<template>
    <div class="operations-history">
        <div class="container mt-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1 fw-bold">История операций</h2>
                    <p class="text-muted mb-0" v-if="pagination.total > 0">
                        Всего операций: <strong>{{ pagination.total }}</strong>
                    </p>
                </div>
                <div>
                    <a href="/" class="btn btn-outline-secondary me-2">
                        <span>←</span> Назад
                    </a>
                    <button @click="handleLogout" class="btn btn-outline-danger">
                        Выйти
                    </button>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <span>🔍</span> Поиск по описанию
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <span>🔎</span>
                                </span>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="searchQuery"
                                    @input="handleSearch"
                                    placeholder="Введите текст для поиска..."
                                />
                                <button 
                                    v-if="searchQuery" 
                                    class="btn btn-outline-secondary" 
                                    type="button"
                                    @click="clearSearch"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <span>📊</span> Сортировка
                            </label>
                            <div class="d-flex gap-2">
                                <select 
                                    class="form-select" 
                                    v-model="sortBy" 
                                    @change="handleSortChange"
                                >
                                    <option value="created_at">📅 По дате</option>
                                    <option value="amount">💰 По сумме</option>
                                    <option value="type">🏷️ По типу</option>
                                    <option value="description">📝 По описанию</option>
                                </select>
                                <button 
                                    class="btn btn-outline-primary" 
                                    @click="toggleSortOrder"
                                    :title="sortOrder === 'asc' ? 'По возрастанию' : 'По убыванию'"
                                    type="button"
                                >
                                    <span v-if="sortOrder === 'asc'">↑</span>
                                    <span v-else>↓</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operations List -->
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-3 text-muted">Загрузка операций...</p>
            </div>

            <div v-else-if="operations.length === 0" class="text-center py-5">
                <div class="mb-3" style="font-size: 4rem;">📋</div>
                <h4 class="text-muted">Операций не найдено</h4>
                <p class="text-muted">Попробуйте изменить параметры поиска</p>
            </div>

            <div v-else class="operations-list">
                <div 
                    v-for="operation in operations" 
                    :key="operation.id"
                    class="operation-card mb-3"
                >
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="operation-icon" :class="operation.type">
                                        <span v-if="operation.type === 'deposit'">⬆️</span>
                                        <span v-else>⬇️</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="operation-date">
                                        <small class="text-muted d-block">Дата</small>
                                        <strong>{{ formatDate(operation.created_at) }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="operation-type">
                                        <small class="text-muted d-block">Тип</small>
                                        <span
                                            class="badge"
                                            :class="{
                                                'bg-success': operation.type === 'deposit',
                                                'bg-danger': operation.type === 'withdrawal',
                                            }"
                                            style="font-size: 0.9rem; padding: 0.5rem 0.75rem;"
                                        >
                                            {{ operation.type === 'deposit' ? 'Начисление' : 'Списание' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="operation-description">
                                        <small class="text-muted d-block">Описание</small>
                                        <strong>{{ operation.description }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="operation-amount" :class="operation.type">
                                        <small class="text-muted d-block">Сумма</small>
                                        <strong 
                                            class="fs-5"
                                            :class="{
                                                'text-success': operation.type === 'deposit',
                                                'text-danger': operation.type === 'withdrawal',
                                            }"
                                        >
                                            <span v-if="operation.type === 'deposit'">+</span>
                                            <span v-else>-</span>
                                            {{ formatBalance(operation.amount) }} ₽
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <nav v-if="pagination.last_page > 1" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                        <a 
                            class="page-link" 
                            href="#" 
                            @click.prevent="changePage(pagination.current_page - 1)"
                        >
                            ← Предыдущая
                        </a>
                    </li>
                    <li
                        v-for="page in getVisiblePages()"
                        :key="page"
                        class="page-item"
                        :class="{ active: page === pagination.current_page, disabled: page === '...' }"
                    >
                        <a 
                            v-if="page !== '...'"
                            class="page-link" 
                            href="#" 
                            @click.prevent="changePage(page)"
                        >
                            {{ page }}
                        </a>
                        <span v-else class="page-link">...</span>
                    </li>
                    <li
                        class="page-item"
                        :class="{ disabled: pagination.current_page === pagination.last_page }"
                    >
                        <a 
                            class="page-link" 
                            href="#" 
                            @click.prevent="changePage(pagination.current_page + 1)"
                        >
                            Следующая →
                        </a>
                    </li>
                </ul>
                <div class="text-center mt-2 text-muted">
                    Страница {{ pagination.current_page }} из {{ pagination.last_page }}
                </div>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const operations = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const sortBy = ref('created_at');
const sortOrder = ref('desc');
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
});

let searchTimeout = null;

const loadOperations = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
        };
        if (searchQuery.value) {
            params.search = searchQuery.value;
        }

        const response = await axios.get('/operations', { 
            params,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Ответ от сервера:', response.data);
        console.log('Тип ответа:', typeof response.data);
        console.log('Есть operations?', !!response.data?.operations);
        console.log('Есть pagination?', !!response.data?.pagination);
        
        if (response.data && response.data.operations) {
            operations.value = Array.isArray(response.data.operations) 
                ? response.data.operations 
                : [];
            pagination.value = response.data.pagination || {
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: 0,
            };
            console.log('Загружено операций:', operations.value.length);
            console.log('Пагинация:', pagination.value);
        } else {
            console.error('Неверный формат ответа:', response.data);
            operations.value = [];
            pagination.value = {
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: 0,
            };
        }
    } catch (error) {
        console.error('Ошибка загрузки операций:', error);
        if (error.response) {
            console.error('Данные ошибки:', error.response.data);
            console.error('Статус:', error.response.status);
        }
    } finally {
        loading.value = false;
    }
};

const handleSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        loadOperations(1);
    }, 500);
};

const handleSortChange = () => {
    // При изменении поля сортировки сбрасываем на первую страницу
    pagination.value.current_page = 1;
    loadOperations(1);
};

const toggleSortOrder = () => {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    // При изменении порядка сортировки сбрасываем на первую страницу
    pagination.value.current_page = 1;
    loadOperations(1);
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        loadOperations(page);
    }
};

const clearSearch = () => {
    searchQuery.value = '';
    loadOperations(1);
};

const getVisiblePages = () => {
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const pages = [];
    
    if (last <= 7) {
        // Если страниц мало, показываем все
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        // Показываем первую страницу
        pages.push(1);
        
        if (current > 3) {
            pages.push('...');
        }
        
        // Показываем страницы вокруг текущей
        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
        
        if (current < last - 2) {
            pages.push('...');
        }
        
        // Показываем последнюю страницу
        pages.push(last);
    }
    
    return pages;
};

const formatBalance = (amount) => {
    return new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU');
};

const handleLogout = async () => {
    try {
        await axios.post('/logout');
        window.location.href = '/login';
    } catch (error) {
        console.error('Ошибка выхода:', error);
        window.location.href = '/login';
    }
};

onMounted(() => {
    // Всегда загружаем операции через AJAX при монтировании компонента
    loadOperations(1);
});
</script>

<style scoped>
.operations-history {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding-bottom: 2rem;
}

.operation-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.operation-card:hover {
    transform: translateY(-2px);
}

.operation-card .card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.operation-card .card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.operation-card .operation-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto;
}

.operation-card .operation-icon.deposit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.operation-card .operation-icon.withdrawal {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.operation-date strong {
    color: #495057;
    font-size: 0.95rem;
}

.operation-description strong {
    color: #212529;
    font-size: 0.95rem;
}

.operation-amount strong {
    font-weight: 700;
}

.pagination .page-link {
    border-radius: 0.375rem;
    margin: 0 0.25rem;
    border: 1px solid #dee2e6;
    color: #495057;
    transition: all 0.2s ease;
}

.pagination .page-link:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    transform: translateY(-1px);
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: #667eea;
    color: white;
}

.pagination .page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
}

.card {
    border-radius: 0.75rem;
}

.input-group-text {
    border-radius: 0.375rem 0 0 0.375rem;
}

.form-select, .form-control {
    border-radius: 0.375rem;
}

.btn {
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .operation-card .row > div {
        margin-bottom: 1rem;
    }
    
    .operation-card .row > div:last-child {
        margin-bottom: 0;
    }
    
    .operation-icon {
        margin-bottom: 0.5rem !important;
    }
}
</style>

