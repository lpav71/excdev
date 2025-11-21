<template>
    <div class="dashboard-page">
        <div class="container mt-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1 fw-bold text-white">Главная страница</h1>
                    <p class="mb-0">
                        <span class="auto-refresh-indicator" :class="{ active: !loading }">
                            <span class="dot"></span>
                            Автообновление каждые 5 секунд
                        </span>
                    </p>
                </div>
                <div>
                    <a href="/operations" class="btn btn-primary me-2">
                        <span>📊</span> История операций
                    </a>
                    <button @click="handleLogout" class="btn btn-danger">
                        Выйти
                    </button>
                </div>
            </div>

            <!-- Balance Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="balance-card">
                        <div class="card shadow-lg border-0">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="balance-info">
                                            <div class="balance-label">
                                                <span class="icon">💰</span>
                                                <span>Текущий баланс</span>
                                            </div>
                                            <div class="balance-amount">
                                                {{ formatBalance(balance) }} <span class="currency">₽</span>
                                            </div>
                                            <div class="balance-stats mt-3">
                                                <div class="stat-item">
                                                    <span class="stat-label">Начислений:</span>
                                                    <span class="stat-value text-success">
                                                        {{ getDepositsCount() }}
                                                    </span>
                                                </div>
                                                <div class="stat-item">
                                                    <span class="stat-label">Списаний:</span>
                                                    <span class="stat-value text-danger">
                                                        {{ getWithdrawalsCount() }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="balance-icon">
                                            <div class="icon-circle">
                                                <span class="icon-emoji">💳</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Operations -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold">
                                    <span>📋</span> Последние 5 операций
                                </h5>
                                <a href="/operations" class="btn btn-sm btn-outline-primary">
                                    Все операции →
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div v-if="loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                                <p class="mt-3 text-muted">Обновление данных...</p>
                            </div>
                            <div v-else-if="operations.length === 0" class="text-center py-5">
                                <div class="mb-3" style="font-size: 4rem;">📭</div>
                                <h5 class="text-muted">Операций пока нет</h5>
                                <p class="text-muted">Ваши операции появятся здесь</p>
                            </div>
                            <div v-else class="operations-list">
                                <div 
                                    v-for="operation in operations" 
                                    :key="operation.id"
                                    class="operation-item"
                                >
                                    <div class="d-flex align-items-center p-3 border-bottom">
                                        <div class="operation-icon-small me-3" :class="operation.type">
                                            <span v-if="operation.type === 'deposit'">⬆️</span>
                                            <span v-else>⬇️</span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="operation-main-info">
                                                <strong class="operation-description">{{ operation.description }}</strong>
                                                <small class="text-muted d-block mt-1">
                                                    {{ formatDate(operation.created_at) }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="operation-amount-info text-end">
                                            <span
                                                class="badge mb-2"
                                                :class="{
                                                    'bg-success': operation.type === 'deposit',
                                                    'bg-danger': operation.type === 'withdrawal',
                                                }"
                                                style="font-size: 0.85rem; padding: 0.4rem 0.6rem;"
                                            >
                                                {{ operation.type === 'deposit' ? 'Начисление' : 'Списание' }}
                                            </span>
                                            <div 
                                                class="operation-amount"
                                                :class="{
                                                    'text-success': operation.type === 'deposit',
                                                    'text-danger': operation.type === 'withdrawal',
                                                }"
                                            >
                                                <strong class="fs-5">
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
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// Получаем начальные данные из data-атрибутов
const appElement = document.getElementById('dashboard-app');
const initialBalance = appElement?.dataset.balance ? parseFloat(appElement.dataset.balance) : 0;
const initialOperations = appElement?.dataset.operations ? JSON.parse(appElement.dataset.operations) : [];

const balance = ref(initialBalance);
const operations = ref(initialOperations.map(op => ({
    ...op,
    created_at: op.created_at || new Date().toISOString(),
})));
const loading = ref(false);
let refreshInterval = null;

const loadData = async () => {
    try {
        const response = await axios.get('/dashboard/data');
        balance.value = parseFloat(response.data.balance);
        operations.value = response.data.operations;
    } catch (error) {
        console.error('Ошибка загрузки данных:', error);
    } finally {
        loading.value = false;
    }
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

const getDepositsCount = () => {
    return operations.value.filter(op => op.type === 'deposit').length;
};

const getWithdrawalsCount = () => {
    return operations.value.filter(op => op.type === 'withdrawal').length;
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
    loadData();
    // Обновление каждые 5 секунд (T = 5)
    refreshInterval = setInterval(loadData, 5000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<style scoped>
.dashboard-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #8a9be2 0%, #986dd1 100%);
    padding-bottom: 2rem;
}

.balance-card {
    animation: slideInDown 0.5s ease;
}

.balance-card .card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 1rem;
    overflow: hidden;
}

.balance-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.balance-label .icon {
    font-size: 1.5rem;
}

.balance-amount {
    font-size: 3.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.balance-amount .currency {
    font-size: 2rem;
    opacity: 0.8;
}

.balance-icon {
    position: relative;
}

.icon-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.icon-emoji {
    font-size: 4rem;
}

.balance-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-label {
    opacity: 0.8;
    font-size: 0.9rem;
}

.stat-value {
    font-weight: 700;
    font-size: 1.1rem;
}

.operations-list {
    max-height: 600px;
    overflow-y: auto;
}

.operation-item {
    transition: all 0.2s ease;
    border-radius: 0.5rem;
}

.operation-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.operation-item:last-child .border-bottom {
    border-bottom: none !important;
}

.operation-icon-small {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.operation-icon-small.deposit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.operation-icon-small.withdrawal {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.operation-description {
    color: #212529;
    font-size: 1rem;
}

.operation-amount-info {
    min-width: 150px;
}

.auto-refresh-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    background: rgba(255, 255, 255, 0.15);
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
    backdrop-filter: blur(10px);
}

.auto-refresh-indicator .dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.6);
    display: inline-block;
    flex-shrink: 0;
}

.auto-refresh-indicator.active .dot {
    background-color: #28a745;
    box-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(0.8);
    }
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    border-radius: 1rem;
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.btn {
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
}

.operations-list::-webkit-scrollbar {
    width: 6px;
}

.operations-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.operations-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.operations-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}

@media (max-width: 768px) {
    .balance-amount {
        font-size: 2.5rem;
    }
    
    .balance-amount .currency {
        font-size: 1.5rem;
    }
    
    .icon-circle {
        width: 80px;
        height: 80px;
    }
    
    .icon-emoji {
        font-size: 2.5rem;
    }
    
    .balance-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .operation-item .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .operation-amount-info {
        width: 100%;
        text-align: left !important;
        margin-top: 1rem;
    }
}
</style>

