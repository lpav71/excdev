<template>
    <div class="operations-history">
        <!-- Фоновые элементы -->
        <div class="background-elements">
            <div class="bg-circle circle-1"></div>
            <div class="bg-circle circle-2"></div>
            <div class="bg-circle circle-3"></div>
            <div class="bg-blur"></div>
        </div>

        <div class="container">
            <!-- Заголовок -->
            <header class="operations-header">
                <div class="header-content">
                    <div class="header-info">
                        <h1 class="page-title">История операций</h1>
                        <p class="page-subtitle" v-if="pagination.total > 0">
                            Всего операций: <span class="count">{{ pagination.total }}</span>
                        </p>
                    </div>
                    <div class="header-actions">
                        <div class="refresh-indicator" :class="{ 'refreshing': loading }">
                            <div class="indicator-dot"></div>
                            <span>Автообновление</span>
                        </div>
                        <button class="btn btn-secondary" @click="loadOperations">
                            <i class="icon-refresh"></i>
                        </button>
                        <a href="/" class="btn btn-outline">
                            <i class="icon-arrow-left"></i>
                            Назад
                        </a>
                        <button @click="handleLogout" class="btn btn-outline">
                            <i class="icon-logout"></i>
                            Выйти
                        </button>
                    </div>
                </div>
            </header>

            <!-- Основной контент -->
            <main class="operations-content">
                <!-- Карточка фильтров -->
                <section class="filters-section">
                    <div class="filters-card">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="icon-search"></i>
                                    Поиск по описанию
                                </label>
                                <div class="search-input-container">
                                    <input
                                        type="text"
                                        class="search-input"
                                        v-model="searchQuery"
                                        @input="handleSearch"
                                        placeholder="Введите текст для поиска..."
                                    />
                                    <button
                                        v-if="searchQuery"
                                        class="clear-search"
                                        @click="clearSearch"
                                    >
                                        <i class="icon-close"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">
                                    <i class="icon-sort"></i>
                                    Сортировка
                                </label>
                                <div class="sort-controls">
                                    <select
                                        class="sort-select"
                                        v-model="sortBy"
                                        @change="handleSortChange"
                                    >
                                        <option value="created_at">По дате</option>
                                        <option value="amount">По сумме</option>
                                        <option value="type">По типу</option>
                                        <option value="description">По описанию</option>
                                    </select>
                                    <button
                                        class="sort-order-btn"
                                        @click="toggleSortOrder"
                                        :class="{ 'active': sortOrder === 'desc' }"
                                    >
                                        <i class="fas" :class="sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Список операций -->
                <section class="operations-section">
                    <!-- Состояние загрузки -->
                    <div v-if="loading" class="loading-state">
                        <div class="loading-spinner"></div>
                        <p>Загрузка операций...</p>
                    </div>

                    <!-- Состояние пустого списка -->
                    <div v-else-if="operations.length === 0" class="empty-state">
                        <div class="empty-icon">
                            <i class="icon-document"></i>
                        </div>
                        <h3>Операций не найдено</h3>
                        <p>Попробуйте изменить параметры поиска</p>
                        <button class="btn btn-primary" @click="clearFilters">
                            <i class="icon-refresh"></i>
                            Сбросить фильтры
                        </button>
                    </div>

                    <!-- Сетка операций -->
                    <div v-else class="operations-grid">
                        <div
                            v-for="operation in operations"
                            :key="operation.id"
                            class="operation-card"
                            :class="operation.type"
                        >
                            <div class="card-header">
                                <div class="operation-type-badge" :class="operation.type">
                                    <i class="icon" :class="operation.type === 'deposit' ? 'icon-arrow-up' : 'icon-arrow-down'"></i>
                                    {{ operation.type === 'deposit' ? 'Начисление' : 'Списание' }}
                                </div>
                                <div class="operation-date">
                                    {{ formatDate(operation.created_at) }}
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="operation-description">
                                    <h4>{{ operation.description }}</h4>
                                </div>

                                <div class="operation-amount" :class="operation.type">
                                    <span class="amount-value">
                                        {{ operation.type === 'deposit' ? '+' : '-' }}
                                        {{ formatBalance(operation.amount) }} ₽
                                    </span>
                                    <div class="amount-indicator" :class="operation.type"></div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="operation-meta">
                                    <span class="meta-item">
                                        <i class="icon-clock"></i>
                                        {{ formatTime(operation.created_at) }}
                                    </span>
                                    <span class="meta-item">
                                        <i class="icon-hash"></i>
                                        ID: {{ operation.id }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Пагинация -->
                    <div v-if="pagination.last_page > 1" class="pagination-section">
                        <div class="pagination-info">
                            Показано {{ getDisplayedRange() }} из {{ pagination.total }} операций
                        </div>

                        <div class="pagination-controls">
                            <button
                                class="pagination-btn"
                                :disabled="pagination.current_page === 1"
                                @click="changePage(pagination.current_page - 1)"
                            >
                                <i class="icon-arrow-left"></i>
                                Назад
                            </button>

                            <div class="pagination-pages">
                                <button
                                    v-for="page in getVisiblePages()"
                                    :key="page"
                                    class="page-btn"
                                    :class="{
                                        'active': page === pagination.current_page,
                                        'ellipsis': page === '...'
                                    }"
                                    @click="page !== '...' && changePage(page)"
                                    :disabled="page === '...'"
                                >
                                    {{ page }}
                                </button>
                            </div>

                            <button
                                class="pagination-btn"
                                :disabled="pagination.current_page === pagination.last_page"
                                @click="changePage(pagination.current_page + 1)"
                            >
                                Вперед
                                <i class="icon-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    operations: {
        type: Array,
        required: true
    },
    pagination: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            sortBy: 'created_at',
            sortOrder: 'desc'
        })
    }
});

const operations = ref(props.operations);
const searchQuery = ref(props.filters.search || '');
const sortBy = ref(props.filters.sortBy || 'created_at');
const sortOrder = ref(props.filters.sortOrder || 'desc');
const pagination = ref(props.pagination);
const loading = ref(false);

let searchTimeout = null;
let refreshInterval = null;
let lastRefreshTime = null;
let consecutiveErrors = 0;
let userActivityTimer = null;
let isTabActive = true;

// Конфигурация умного автообновления
const refreshConfig = {
    baseInterval: 15000, // Базовый интервал 15 секунд
    activeInterval: 10000, // 10 секунд при активном пользователе
    idleInterval: 30000, // 30 секунд при неактивном пользователе
    maxInterval: 120000, // Максимальный интервал 2 минуты
    errorBackoffMultiplier: 2,
    maxConsecutiveErrors: 3,
    minOperationsForReducedRefresh: 50, // Уменьшить частоту для больших наборов данных
    userActivityTimeout: 30000 // 30 секунд неактивности
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
    loadOperations(1);
};

const toggleSortOrder = () => {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
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

const clearFilters = () => {
    searchQuery.value = '';
    sortBy.value = 'created_at';
    sortOrder.value = 'desc';
    loadOperations(1);
};

const getVisiblePages = () => {
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const pages = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        pages.push(1);

        if (current > 3) {
            pages.push('...');
        }

        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (current < last - 2) {
            pages.push('...');
        }

        pages.push(last);
    }

    return pages;
};

const getDisplayedRange = () => {
    const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1;
    const end = Math.min(start + pagination.value.per_page - 1, pagination.value.total);
    return `${start}-${end}`;
};

const formatBalance = (amount) => {
    return new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const loadOperations = (page = 1, isAutoRefresh = false) => {
    // Пропустить если уже загружается, чтобы предотвратить дублирующие запросы
    if (loading.value && !isAutoRefresh) return;
    
    loading.value = true;

    const params = {
        page,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    };
    if (searchQuery.value) {
        params.search = searchQuery.value;
    }

    // Добавить параметр для обхода кэша при автообновлении
    if (isAutoRefresh) {
        params._t = Date.now();
    }

    router.get('/operations', params, {
        preserveState: true,
        preserveScroll: true,
        only: ['operations', 'pagination', 'filters'],
        replace: true,
        onSuccess: () => {
            loading.value = false;
            lastRefreshTime = Date.now();
        },
        onFinish: () => {
            loading.value = false;
        },
        onError: () => {
            loading.value = false;
            consecutiveErrors++;
        },
    });
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

// Синхронизация props с локальными переменными (без immediate, чтобы избежать лишних обновлений)
watch(() => props.operations, (newOperations) => {
    if (JSON.stringify(operations.value) !== JSON.stringify(newOperations)) {
        operations.value = newOperations;
    }
});

watch(() => props.pagination, (newPagination) => {
    if (JSON.stringify(pagination.value) !== JSON.stringify(newPagination)) {
        pagination.value = { ...newPagination };
    }
}, { deep: true });

// Фильтры обновляем только если они действительно изменились
watch(() => props.filters, (newFilters) => {
    if (searchQuery.value !== (newFilters.search || '')) {
        searchQuery.value = newFilters.search || '';
    }
    if (sortBy.value !== (newFilters.sortBy || 'created_at')) {
        sortBy.value = newFilters.sortBy || 'created_at';
    }
    if (sortOrder.value !== (newFilters.sortOrder || 'desc')) {
        sortOrder.value = newFilters.sortOrder || 'desc';
    }
}, { deep: true });

// Функции умного автообновления
const calculateRefreshInterval = () => {
    // Базовый интервал на основе размера набора данных
    let interval = pagination.value.total > refreshConfig.minOperationsForReducedRefresh
        ? refreshConfig.baseInterval * 1.5
        : refreshConfig.baseInterval;

    // Корректировка на основе активности пользователя
    if (!isTabActive) {
        interval = refreshConfig.idleInterval;
    } else if (isUserActive()) {
        interval = refreshConfig.activeInterval;
    }

    // Применить экспоненциальную задержку при ошибках
    if (consecutiveErrors > 0) {
        interval = Math.min(
            interval * Math.pow(refreshConfig.errorBackoffMultiplier, consecutiveErrors),
            refreshConfig.maxInterval
        );
    }

    return interval;
};

const isUserActive = () => {
    return Date.now() - (lastRefreshTime || 0) < refreshConfig.userActivityTimeout;
};

const checkForUpdates = async () => {
    if (!isTabActive) return;

    try {
        // Использовать легковесную проверку новых операций
        const response = await axios.get('/operations', {
            params: {
                page: 1,
                per_page: 5, // Проверить последние 5 операций для лучшей точности
                sort_by: 'created_at',
                sort_order: 'desc'
            }
        });

        const latestOperations = response.data.operations || [];
        const currentOperations = operations.value.slice(0, 5);
        
        // Проверить, есть ли новые операции
        const hasNewOperations = latestOperations.some(latestOp =>
            !currentOperations.some(currentOp => currentOp.id === latestOp.id)
        );

        if (hasNewOperations || pagination.value.current_page === 1) {
            await loadOperations(pagination.value.current_page, true);
        }

        consecutiveErrors = 0;
        lastRefreshTime = Date.now();

    } catch (error) {
        console.error('Ошибка автообновления:', error);
        consecutiveErrors++;
        
        if (consecutiveErrors >= refreshConfig.maxConsecutiveErrors) {
            console.warn('Превышено максимальное количество ошибок, приостанавливаем автообновление');
            stopRefresh();
        }
    }
};

const startRefresh = () => {
    stopRefresh();
    
    const interval = calculateRefreshInterval();
    console.log(`Запуск автообновления с интервалом ${interval/1000} секунд`);
    
    refreshInterval = setInterval(() => {
        checkForUpdates();
    }, interval);
};

const stopRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

const handleUserActivity = () => {
    if (userActivityTimer) {
        clearTimeout(userActivityTimer);
    }
    
    userActivityTimer = setTimeout(() => {
        // Пользователь стал неактивным, скорректировать частоту обновления
        startRefresh();
    }, refreshConfig.userActivityTimeout);
};

const handleVisibilityChange = () => {
    isTabActive = !document.hidden;
    
    if (isTabActive) {
        // Вкладка стала активной - обновить немедленно и перезапустить
        loadOperations(pagination.value.current_page);
        startRefresh();
    } else {
        // Вкладка стала неактивной - уменьшить частоту обновления
        startRefresh();
    }
};

const initializeSmartRefresh = () => {
    // Настроить отслеживание активности пользователя
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, handleUserActivity, { passive: true });
    });

    // Настроить отслеживание видимости вкладки
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Запустить систему автообновления
    startRefresh();
};

const cleanupRefreshSystem = () => {
    stopRefresh();
    
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    if (userActivityTimer) {
        clearTimeout(userActivityTimer);
    }
    
    // Удалить обработчики событий
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.removeEventListener(event, handleUserActivity);
    });
    
    document.removeEventListener('visibilitychange', handleVisibilityChange);
};

onMounted(() => {
    operations.value = props.operations;
    pagination.value = props.pagination;
    searchQuery.value = props.filters.search || '';
    sortBy.value = props.filters.sortBy || 'created_at';
    sortOrder.value = props.filters.sortOrder || 'desc';

    // Инициализировать умное автообновление
    initializeSmartRefresh();

    // Очистка событий при размонтировании
    onUnmounted(() => {
        cleanupRefreshSystem();
    });
});
</script>

<style scoped>

/* Исправление проблемы компиляции Vite */
/* Базовые стили */
.operations-history {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow-x: hidden;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 2;
}

/* Фоновые элементы */
.background-elements {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.bg-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.circle-1 {
    width: 300px;
    height: 300px;
    top: -150px;
    right: -100px;
}

.circle-2 {
    width: 200px;
    height: 200px;
    bottom: 100px;
    left: -50px;
}

.circle-3 {
    width: 150px;
    height: 150px;
    top: 50%;
    right: 10%;
}

.bg-blur {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
}

/* Заголовок */
.operations-header {
    padding: 2rem 0;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-subtitle {
    color: rgba(255, 255, 255, 0.8);
    margin: 0.5rem 0 0 0;
    font-size: 1.1rem;
}

.count {
    color: #10b981;
    font-weight: 600;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.refresh-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.9rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.refresh-indicator.refreshing .indicator-dot {
    background: #10b981;
    animation: pulse 1.5s infinite;
}

.indicator-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transition: all 0.3s ease;
}

/* Кнопки */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
    backdrop-filter: blur(10px);
}

.btn-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Секция фильтров */
.filters-section {
    margin-bottom: 2rem;
}

.filters-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.filter-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
}

.search-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 1rem 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.search-input:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.4);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
}

.clear-search {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.clear-search:hover {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
}

.sort-controls {
    display: flex;
    gap: 0.5rem;
}

.sort-select {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    font-size: 1rem;
    background: rgba(255, 255, 255, 0.1);
    color: #2a2fa8;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sort-select:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.4);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
}

.sort-order-btn {
    padding: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.3s ease;
}

.sort-order-btn:hover,
.sort-order-btn.active {
    border-color: rgba(255, 255, 255, 0.4);
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Секция операций */
.operations-section {
    min-height: 400px;
}

/* Состояние загрузки */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: rgba(255, 255, 255, 0.8);
}

.loading-spinner {
    width: 48px;
    height: 48px;
    border: 3px solid rgba(25, 255, 255, 0.3);
    border-top: 3px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

/* Состояние пустого списка */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: rgba(255, 255, 255, 0.8);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state h3 {
    color: white;
    margin-bottom: 0.5rem;
}

/* Сетка операций */
.operations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.operation-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid rgba(25, 255, 255, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.operation-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    border-color: rgba(255, 255, 255, 0.3);
}

.operation-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    transition: all 0.3s ease;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.operation-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.operation-date {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    font-weight: 500;
}

.card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.operation-description h4 {
    color: white;
    font-weight: 600;
    margin: 0;
    font-size: 1.1rem;
}

.operation-amount {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.amount-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.operation-amount.deposit .amount-value {
    color: #10b981;
}

.operation-amount.withdrawal .amount-value {
    color: #ef4444;
}

.amount-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.card-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1rem;
}

.operation-meta {
    display: flex;
    gap: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.85rem;
}

/* Секция пагинации */
.pagination-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
    margin-top: 2rem;
}

.pagination-info {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.pagination-btn:hover:not(:disabled) {
    border-color: rgba(255, 255, 255, 0.4);
    color: white;
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.15);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-pages {
    display: flex;
    gap: 0.5rem;
}

.page-btn {
    padding: 0.75rem 1rem;
    border: 1px solid rgba(25, 25, 255, 0.2);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.9);
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 45px;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.page-btn:hover:not(.ellipsis):not(.active) {
    border-color: rgba(255, 255, 255, 0.4);
    color: white;
    background: rgba(255, 255, 255, 0.15);
}

.page-btn.active {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-color: #10b981;
}

.page-btn.ellipsis {
    cursor: default;
    background: transparent;
    border: none;
}

/* Иконки */
[class^="icon-"] {
    font-family: 'Icons',serif !important;
    font-style: normal;
    font-weight: normal;
    font-variant: normal;
    text-transform: none;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
}

.icon-refresh:before { content: "🔄"; }
.icon-logout:before { content: "🚪"; }
.icon-document:before { content: "📄"; }
.icon-arrow-up:before { content: "⬆️"; }
.icon-arrow-down:before { content: "⬇️"; }
.icon-arrow-left:before { content: "←"; }
.icon-arrow-right:before { content: "→"; }
.icon-search:before { content: "🔍"; }
.icon-sort:before { content: "↕️"; }
.icon-close:before { content: "✕"; }
.icon-clock:before { content: "⏰"; }
.icon-hash:before { content: "#"; }

/* Анимации */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
}

/* Адаптивный дизайн */
@media (max-width: 1024px) {
    .operations-grid {
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 16px;
    }

    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .page-title {
        font-size: 2rem;
    }

    .header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .filter-row {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .operations-grid {
        grid-template-columns: 1fr;
    }

    .pagination-controls {
        flex-direction: column;
        gap: 1rem;
    }
}

@media (max-width: 480px) {
    .card-body {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .operation-meta {
        flex-direction: column;
        gap: 0.5rem;
    }

    .header-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        justify-content: center;
    }

    .filters-card {
        padding: 1.5rem;
    }
}
</style>
