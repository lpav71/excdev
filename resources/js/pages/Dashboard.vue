<template>
    <div class="dashboard">
        <!-- Background Elements -->
        <div class="background-elements">
            <div class="bg-circle circle-1"></div>
            <div class="bg-circle circle-2"></div>
            <div class="bg-circle circle-3"></div>
            <div class="bg-blur"></div>
        </div>

        <div class="container">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-content">
                    <div class="header-info">
                        <h1 class="page-title">Финансовый обзор</h1>
                        <p class="page-subtitle">Управляйте вашими финансами эффективно</p>
                    </div>
                    <div class="header-actions">
                        <div class="refresh-indicator" :class="{ 'refreshing': loading }">
                            <div class="indicator-dot"></div>
                            <span>Автообновление</span>
                        </div>
                        <button class="btn btn-secondary" @click="loadData">
                            <i class="icon-refresh"></i>
                        </button>
                        <a href="/operations" class="btn btn-primary">
                            <i class="icon-chart"></i>
                            История операций
                        </a>
                        <button @click="handleLogout" class="btn btn-outline">
                            <i class="icon-logout"></i>
                            Выйти
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="dashboard-content">
                <!-- Balance Overview -->
                <section class="balance-section">
                    <div class="balance-card">
                        <div class="balance-header">
                            <div class="balance-title">
                                <i class="icon-wallet"></i>
                                <h2>Текущий баланс</h2>
                            </div>
                            <div class="balance-stats">
                                <div class="stat positive">
                                    <span class="stat-label">Начислений</span>
                                    <span class="stat-value">{{ getDepositsCount() }}</span>
                                </div>
                                <div class="stat negative">
                                    <span class="stat-label">Списаний</span>
                                    <span class="stat-value">{{ getWithdrawalsCount() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="balance-amount">
                            <span class="amount">{{ formatBalance(props.balance) }}</span>
                            <span class="currency">₽</span>
                        </div>
                        <div class="balance-graph">
                            <div class="graph-bar" v-for="n in 12" :key="n"
                                 :style="{ height: `${Math.random() * 60 + 20}%` }"></div>
                        </div>
                    </div>
                </section>

                <!-- Recent Transactions -->
                <section class="transactions-section">
                    <div class="section-header">
                        <h2>Последние операции</h2>
                        <a href="/operations" class="view-all-link">
                            Все операции
                            <i class="icon-arrow-right"></i>
                        </a>
                    </div>

                    <div class="transactions-list">
                        <!-- Loading State -->
                        <div v-if="loading" class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Обновление данных...</p>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="props.operations.length === 0" class="empty-state">
                            <div class="empty-icon">
                                <i class="icon-document"></i>
                            </div>
                            <h3>Операций пока нет</h3>
                            <p>Здесь появятся ваши финансовые операции</p>
                        </div>

                        <!-- Transactions List -->
                        <div v-else class="transactions">
                            <div
                                v-for="operation in props.operations"
                                :key="operation.id"
                                class="transaction-item"
                                :class="operation.type"
                            >
                                <div class="transaction-icon">
                                    <i :class="getOperationIcon(operation.type)"></i>
                                </div>
                                <div class="transaction-details">
                                    <h4 class="transaction-title">{{ operation.description }}</h4>
                                    <p class="transaction-date">{{ formatDate(operation.created_at) }}</p>
                                </div>
                                <div class="transaction-amount" :class="operation.type">
                                    <span class="amount">
                                        {{ operation.type === 'deposit' ? '+' : '-' }}
                                        {{ formatBalance(operation.amount) }} ₽
                                    </span>
                                    <span class="transaction-type">
                                        {{ operation.type === 'deposit' ? 'Начисление' : 'Списание' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Quick Stats -->
                <section class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon income">
                                <i class="icon-trend-up"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Общий доход</h3>
                                <p class="stat-value">{{ formatBalance(getTotalIncome()) }} ₽</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon expense">
                                <i class="icon-trend-down"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Общие расходы</h3>
                                <p class="stat-value">{{ formatBalance(getTotalExpenses()) }} ₽</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon transactions">
                                <i class="icon-activity"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Всего операций</h3>
                                <p class="stat-value">{{ props.operations.length }}</p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    balance: {
        type: Number,
        required: true
    },
    operations: {
        type: Array,
        required: true
    }
});

const loading = ref(false);
let refreshInterval = null;

const loadData = async () => {
    try {
        loading.value = true;
        router.reload({ only: ['balance', 'operations'] });
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
    return date.toLocaleString('ru-RU', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getDepositsCount = () => {
    return props.operations.filter(op => op.type === 'deposit').length;
};

const getWithdrawalsCount = () => {
    return props.operations.filter(op => op.type === 'withdrawal').length;
};

const getTotalIncome = () => {
    return props.operations
                .filter(op => op.type === 'deposit')
                .reduce((sum, op) => sum + op.amount, 0);
};

const getTotalExpenses = () => {
    return props.operations
                .filter(op => op.type === 'withdrawal')
                .reduce((sum, op) => sum + op.amount, 0);
};

const getOperationIcon = (type) => {
    return type === 'deposit' ? 'icon-arrow-up' : 'icon-arrow-down';
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
    refreshInterval = setInterval(loadData, 5000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<style scoped>
/* Base Styles */
.dashboard {
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

/* Background Elements */
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

/* Header */
.dashboard-header {
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

/* Buttons */
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

/* Balance Section */
.balance-section {
    margin-bottom: 2rem;
}

.balance-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 2.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.balance-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.balance-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

.balance-title h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.balance-stats {
    display: flex;
    gap: 1.5rem;
}

.stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    min-width: 100px;
}

.stat-label {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}

.stat.positive .stat-value {
    color: #10b981;
}

.stat.negative .stat-value {
    color: #ef4444;
}

.balance-amount {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.amount {
    font-size: 3.5rem;
    font-weight: 800;
    color: white;
    line-height: 1;
}

.currency {
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
}

.balance-graph {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 80px;
    margin-top: 2rem;
}

.graph-bar {
    flex: 1;
    background: linear-gradient(to top, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
    border-radius: 4px 4px 0 0;
    min-height: 20px;
    transition: all 0.3s ease;
}

.graph-bar:hover {
    background: linear-gradient(to top, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.2));
}

/* Transactions Section */
.transactions-section {
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.view-all-link:hover {
    color: white;
    transform: translateX(4px);
}

.transactions-list {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    overflow: hidden;
}

/* Loading State */
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
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top: 3px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

/* Empty State */
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

/* Transactions */
.transactions {
    max-height: 500px;
    overflow-y: auto;
}

.transaction-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    background: transparent;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.transaction-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.transaction-item.deposit .transaction-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.transaction-item.withdrawal .transaction-icon {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.transaction-details {
    flex: 1;
}

.transaction-title {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.transaction-date {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    margin: 0;
}

.transaction-amount {
    text-align: right;
}

.transaction-amount .amount {
    font-size: 1.25rem;
    font-weight: 700;
    display: block;
    margin-bottom: 0.25rem;
}

.transaction-amount.deposit .amount {
    color: #10b981;
}

.transaction-amount.withdrawal .amount {
    color: #ef4444;
}

.transaction-type {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Stats Section */
.stats-section {
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-icon.income {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.stat-icon.expense {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.stat-icon.transactions {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.stat-info h3 {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0 0 0.5rem 0;
}

.stat-value {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

/* Icons */
@font-face {
    font-family: 'Icons';
    src: url('data:application/x-font-woff2;charset=utf-8;base64,/* Simplified icon font base64 would go here */');
}

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
.icon-chart:before { content: "📊"; }
.icon-logout:before { content: "🚪"; }
.icon-wallet:before { content: "💰"; }
.icon-arrow-right:before { content: "→"; }
.icon-document:before { content: "📄"; }
.icon-trend-up:before { content: "📈"; }
.icon-trend-down:before { content: "📉"; }
.icon-activity:before { content: "💹"; }

/* Animations */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(1.1); }
}

/* Scrollbar */
.transactions::-webkit-scrollbar {
    width: 6px;
}

.transactions::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.transactions::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.transactions::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* Responsive Design */
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

    .balance-header {
        flex-direction: column;
        gap: 1.5rem;
    }

    .balance-stats {
        width: 100%;
        justify-content: space-between;
    }

    .amount {
        font-size: 2.5rem;
    }

    .currency {
        font-size: 1.5rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .transaction-item {
        padding: 1.25rem;
    }

    .balance-card {
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .balance-amount {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .transaction-item {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }

    .transaction-amount {
        text-align: left;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
    }

    .btn {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }
}
</style>
