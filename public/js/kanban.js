        // Drag and Drop functionality
// Обновленная часть JavaScript для правильной сортировки по приоритету

class KanbanDragDrop {
    constructor() {
        this.draggedElement = null;
        this.draggedTaskId = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupCSRFToken();
    }

    setupCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            this.csrfToken = token.getAttribute('content');
            console.log('CSRF token found:', this.csrfToken);
        } else {
            console.error('CSRF token not found!');
        }
    }

    setupEventListeners() {
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('dragstart', this.handleDragStart.bind(this));
            card.addEventListener('dragend', this.handleDragEnd.bind(this));
        });

        // Добавляем обработчики на всю колонку (включая заголовок)
        document.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', this.handleDragOver.bind(this));
            column.addEventListener('drop', this.handleDrop.bind(this));
            column.addEventListener('dragenter', this.handleDragEnter.bind(this));
            column.addEventListener('dragleave', this.handleDragLeave.bind(this));
        });
    }

    handleDragStart(e) {
        this.draggedElement = e.target;
        this.draggedTaskId = e.target.dataset.taskId;
        e.target.classList.add('dragging');
        
        console.log('Drag started for task:', this.draggedTaskId);
        
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', e.target.outerHTML);
    }

    handleDragEnd(e) {
        e.target.classList.remove('dragging');
        
        this.draggedElement = null;
        this.draggedTaskId = null;
        
        // Убираем подсветку со всех колонок
        document.querySelectorAll('.kanban-column').forEach(column => {
            column.classList.remove('drag-over');
        });
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        
        // Подсвечиваем всю колонку
        const column = e.currentTarget;
        column.classList.add('drag-over');
    }

    handleDragEnter(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
    }

    handleDragLeave(e) {
        if (!e.currentTarget.contains(e.relatedTarget)) {
            e.currentTarget.classList.remove('drag-over');
        }
    }

    handleDrop(e) {
        e.preventDefault();
        const column = e.currentTarget;
        const newStatus = column.dataset.status;
        
        column.classList.remove('drag-over');
        
        if (this.draggedElement && this.draggedTaskId) {
            const draggedCard = this.draggedElement;
            const taskId = this.draggedTaskId;
            
            // Находим column-content внутри колонки
            const columnContent = column.querySelector('.column-content');
            
            // Обновляем статус задачи на сервере
            this.updateTaskStatus(taskId, newStatus)
                .then(response => {
                    if (response.success) {
                        // Перемещаем элемент в правильную позицию по приоритету
                        this.moveCardToPriorityPosition(draggedCard, columnContent);
                        
                        // Обновляем счетчики
                        this.updateTaskCounts();
                        
                        // Показываем уведомление об успехе
                        this.showToast('Задача успешно перемещена', 'success');
                    } else {
                        this.showToast('Ошибка при перемещении задачи', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error updating task status:', error);
                    this.showToast('Ошибка при перемещении задачи', 'error');
                });
        }
    }

    // Новый метод для перемещения карточки на правильную позицию по приоритету
    moveCardToPriorityPosition(draggedCard, targetColumn) {
        try {
            // Получаем приоритет перемещаемой карточки
            const draggedPriority = parseInt(draggedCard.dataset.priority);
            
            // Удаляем карточку из старой позиции
            if (draggedCard.parentNode) {
                draggedCard.parentNode.removeChild(draggedCard);
            }
            
            // Получаем все карточки в целевой колонке (исключая перемещаемую)
            const existingCards = Array.from(targetColumn.querySelectorAll('.kanban-card'))
                .filter(card => card !== draggedCard);
            
            // Находим правильную позицию для вставки на основе приоритета
            let insertPosition = null;
            
            for (let i = 0; i < existingCards.length; i++) {
                const cardPriority = parseInt(existingCards[i].dataset.priority);
                
                // Вставляем перед первой карточкой с меньшим приоритетом
                if (draggedPriority > cardPriority) {
                    insertPosition = existingCards[i];
                    break;
                }
            }
            
            // Вставляем карточку в правильную позицию
            if (insertPosition) {
                targetColumn.insertBefore(draggedCard, insertPosition);
            } else {
                // Если не нашли позицию, значит приоритет самый низкий - добавляем в конец
                targetColumn.appendChild(draggedCard);
            }
            
            console.log(`Card moved to priority position. Priority: ${draggedPriority}`);
            
        } catch (error) {
            console.error('Error moving card to priority position:', error);
            // Fallback: просто добавляем в конец колонки
            targetColumn.appendChild(draggedCard);
        }
    }

    // Удаляем метод getDragAfterElement так как он больше не нужен

    async updateTaskStatus(taskId, newStatus) {
        try {
            console.log('Updating task:', taskId, 'to status:', newStatus);
            
            const response = await fetch(`/tasks/${taskId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: newStatus
                })
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server error:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const result = await response.json();
            console.log('Update result:', result);
            return result;
        } catch (error) {
            console.error('Error updating task status:', error);
            throw error;
        }
    }

    updateTaskCounts() {
        document.querySelectorAll('.kanban-column').forEach(column => {
            const status = column.dataset.status;
            const count = column.querySelectorAll('.kanban-card').length;
            const countElement = column.querySelector('.task-count');
            if (countElement) {
                countElement.textContent = count;
            }
        });
    }

    showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
}

// Инициализируем drag and drop когда DOM загружен
document.addEventListener('DOMContentLoaded', function() {
    new KanbanDragDrop();
});