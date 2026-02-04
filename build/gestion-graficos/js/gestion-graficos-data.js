/**
 * CLÚSTER GRÁFICOS - MÓDULO DATA
 * Manejo específico de datos y fuentes de información
 */

class ClústerGraficosData {
    constructor(graficosCore) {
        this.core = graficosCore;
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutos
        
        this.init();
    }
    
    /**
     * Inicialización del módulo de datos
     */
    init() {
        console.log('✅ Módulo de datos inicializado');
    }
    
    /**
     * Obtener datos con caché
     */
    async getData(fuente, periodo = 12, filtros = {}) {
        const cacheKey = this.generateCacheKey(fuente, periodo, filtros);
        
        // Verificar caché
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheTimeout) {
                console.log('📦 Datos obtenidos desde caché');
                return cached.data;
            }
        }
        
        try {
            const data = await this.fetchDataFromAPI(fuente, periodo, filtros);
            
            // Guardar en caché
            this.cache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
            
            console.log('🔄 Datos obtenidos desde API');
            return data;
            
        } catch (error) {
            console.error('Error obteniendo datos:', error);
            throw error;
        }
    }
    
    /**
     * Generar clave de caché
     */
    generateCacheKey(fuente, periodo, filtros) {
        const filtrosString = JSON.stringify(filtros);
        return `${fuente}_${periodo}_${filtrosString}`;
    }
    
    /**
     * Obtener datos desde la API
     */
    async fetchDataFromAPI(fuente, periodo, filtros) {
        if (fuente === 'custom') {
            return this.getCustomData();
        }
        
        return await this.core.getDatosGrafico(fuente, periodo, filtros);
    }
    
    /**
     * Obtener datos personalizados
     */
    getCustomData() {
        const editor = document.getElementById('customDataEditor');
        if (!editor || !editor.value.trim()) {
            throw new Error('No hay datos personalizados disponibles');
        }
        
        const validation = this.core.validateCustomData(editor.value);
        if (!validation.valid) {
            throw new Error('Datos personalizados no válidos: ' + validation.message);
        }
        
        // Simular estructura de respuesta de API
        return {
            data: validation.data.map(item => ({
                label: item.label || item.mes || item.nombre,
                valor: parseFloat(item.valor)
            })),
            metadata: {
                fuente: 'custom',
                total_puntos: validation.data.length,
                ultimo_valor: validation.data[validation.data.length - 1]?.valor || 0,
                timestamp: new Date().toISOString()
            }
        };
    }
    
    /**
     * Procesar datos para diferentes tipos de gráficos
     */
    processDataForChart(rawData, chartType) {
        if (!rawData || !rawData.data) {
            return { labels: [], datasets: [] };
        }
        
        const data = rawData.data;
        
        switch (chartType) {
            case 'line':
                return this.processLineData(data);
            case 'bar':
                return this.processBarData(data);
            case 'area':
                return this.processAreaData(data);
            case 'pie':
                return this.processPieData(data);
            case 'doughnut':
                return this.processDoughnutData(data);
            default:
                return this.processLineData(data);
        }
    }
    
    /**
     * Procesar datos para gráfico de líneas
     */
    processLineData(data) {
        return {
            labels: data.map(item => item.label),
            values: data.map(item => item.valor)
        };
    }
    
    /**
     * Procesar datos para gráfico de barras
     */
    processBarData(data) {
        return {
            labels: data.map(item => item.label),
            values: data.map(item => item.valor)
        };
    }
    
    /**
     * Procesar datos para gráfico de área
     */
    processAreaData(data) {
        return {
            labels: data.map(item => item.label),
            values: data.map(item => item.valor)
        };
    }
    
    /**
     * Procesar datos para gráfico de pastel
     */
    processPieData(data) {
        return {
            labels: data.map(item => item.label),
            values: data.map(item => item.valor),
            backgroundColor: this.generateColors(data.length)
        };
    }
    
    /**
     * Procesar datos para gráfico de dona
     */
    processDoughnutData(data) {
        return {
            labels: data.map(item => item.label),
            values: data.map(item => item.valor),
            backgroundColor: this.generateColors(data.length)
        };
    }
    
    /**
     * Generar colores para gráficos
     */
    generateColors(count) {
        const baseColors = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
        ];
        
        const colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
        
        return colors;
    }
    
    /**
     * Obtener estadísticas de los datos
     */
    getDataStatistics(data) {
        if (!data || data.length === 0) {
            return {
                total: 0,
                promedio: 0,
                maximo: 0,
                minimo: 0,
                suma: 0,
                mediana: 0,
                varianza: 0,
                desviacionEstandar: 0
            };
        }
        
        const valores = data.map(item => parseFloat(item.valor));
        const suma = valores.reduce((acc, val) => acc + val, 0);
        const promedio = suma / valores.length;
        const maximo = Math.max(...valores);
        const minimo = Math.min(...valores);
        
        // Mediana
        const sortedValues = [...valores].sort((a, b) => a - b);
        const mid = Math.floor(sortedValues.length / 2);
        const mediana = sortedValues.length % 2 !== 0 
            ? sortedValues[mid] 
            : (sortedValues[mid - 1] + sortedValues[mid]) / 2;
        
        // Varianza y desviación estándar
        const varianza = valores.reduce((acc, val) => acc + Math.pow(val - promedio, 2), 0) / valores.length;
        const desviacionEstandar = Math.sqrt(varianza);
        
        return {
            total: valores.length,
            promedio: promedio,
            maximo: maximo,
            minimo: minimo,
            suma: suma,
            mediana: mediana,
            varianza: varianza,
            desviacionEstandar: desviacionEstandar
        };
    }
    
    /**
     * Validar y limpiar datos
     */
    validateAndCleanData(data) {
        if (!Array.isArray(data)) {
            throw new Error('Los datos deben ser un array');
        }
        
        const cleanedData = [];
        
        for (let i = 0; i < data.length; i++) {
            const item = data[i];
            
            if (!item || typeof item !== 'object') {
                console.warn(`Elemento ${i} omitido: no es un objeto válido`);
                continue;
            }
            
            const label = item.label || item.mes || item.nombre || `Item ${i + 1}`;
            let valor = parseFloat(item.valor);
            
            if (isNaN(valor)) {
                console.warn(`Elemento ${i} omitido: valor no numérico`);
                continue;
            }
            
            cleanedData.push({
                label: String(label).trim(),
                valor: valor
            });
        }
        
        if (cleanedData.length === 0) {
            throw new Error('No se encontraron datos válidos');
        }
        
        return cleanedData;
    }
    
    /**
     * Formatear datos para exportación
     */
    formatForExport(data, format = 'json') {
        switch (format.toLowerCase()) {
            case 'json':
                return JSON.stringify(data, null, 2);
                
            case 'csv':
                return this.convertToCSV(data);
                
            case 'excel':
                return this.convertToExcel(data);
                
            default:
                return JSON.stringify(data, null, 2);
        }
    }
    
    /**
     * Convertir datos a CSV
     */
    convertToCSV(data) {
        if (!data || data.length === 0) {
            return '';
        }
        
        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => 
                headers.map(header => {
                    const value = row[header];
                    return typeof value === 'string' && value.includes(',') 
                        ? `"${value}"` 
                        : value;
                }).join(',')
            )
        ].join('\n');
        
        return csvContent;
    }
    
    /**
     * Convertir datos a formato Excel (simulado)
     */
    convertToExcel(data) {
        // Para una implementación real, se necesitaría una librería como SheetJS
        return this.convertToCSV(data);
    }
    
    /**
     * Interpolación de datos faltantes
     */
    interpolateMissingData(data, method = 'linear') {
        if (!data || data.length < 2) {
            return data;
        }
        
        const result = [...data];
        
        for (let i = 1; i < result.length - 1; i++) {
            if (result[i].valor === null || result[i].valor === undefined) {
                const prevValue = result[i - 1].valor;
                const nextIndex = this.findNextValidIndex(result, i);
                
                if (nextIndex !== -1) {
                    const nextValue = result[nextIndex].valor;
                    
                    if (method === 'linear') {
                        const steps = nextIndex - i + 1;
                        const increment = (nextValue - prevValue) / steps;
                        result[i].valor = prevValue + increment;
                    } else if (method === 'forward') {
                        result[i].valor = prevValue;
                    }
                }
            }
        }
        
        return result;
    }
    
    /**
     * Encontrar siguiente índice con valor válido
     */
    findNextValidIndex(data, startIndex) {
        for (let i = startIndex + 1; i < data.length; i++) {
            if (data[i].valor !== null && data[i].valor !== undefined) {
                return i;
            }
        }
        return -1;
    }
    
    /**
     * Aplicar filtros a los datos
     */
    applyFilters(data, filters) {
        if (!filters || Object.keys(filters).length === 0) {
            return data;
        }
        
        return data.filter(item => {
            for (const [key, value] of Object.entries(filters)) {
                if (item[key] && item[key] !== value) {
                    return false;
                }
            }
            return true;
        });
    }
    
    /**
     * Agrupar datos por período
     */
    groupByPeriod(data, period = 'month') {
        const grouped = {};
        
        data.forEach(item => {
            let key;
            
            if (item.fecha) {
                const date = new Date(item.fecha);
                
                switch (period) {
                    case 'day':
                        key = date.toISOString().split('T')[0];
                        break;
                    case 'week':
                        const weekStart = new Date(date);
                        weekStart.setDate(date.getDate() - date.getDay());
                        key = weekStart.toISOString().split('T')[0];
                        break;
                    case 'month':
                        key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                        break;
                    case 'year':
                        key = String(date.getFullYear());
                        break;
                    default:
                        key = item.label;
                }
            } else {
                key = item.label;
            }
            
            if (!grouped[key]) {
                grouped[key] = {
                    label: key,
                    valor: 0,
                    count: 0
                };
            }
            
            grouped[key].valor += item.valor;
            grouped[key].count += 1;
        });
        
        return Object.values(grouped);
    }
    
    /**
     * Calcular tendencia de los datos
     */
    calculateTrend(data) {
        if (!data || data.length < 2) {
            return { direction: 'neutral', percentage: 0 };
        }
        
        const values = data.map(item => item.valor);
        const first = values[0];
        const last = values[values.length - 1];
        
        if (first === 0) {
            return { direction: 'neutral', percentage: 0 };
        }
        
        const percentage = ((last - first) / first) * 100;
        let direction = 'neutral';
        
        if (percentage > 0.1) {
            direction = 'up';
        } else if (percentage < -0.1) {
            direction = 'down';
        }
        
        return {
            direction: direction,
            percentage: Math.abs(percentage),
            change: last - first
        };
    }
    
    /**
     * Limpiar caché
     */
    clearCache() {
        this.cache.clear();
        console.log('🧹 Caché limpiado');
    }
    
    /**
     * Obtener información del caché
     */
    getCacheInfo() {
        const entries = Array.from(this.cache.entries()).map(([key, value]) => ({
            key: key,
            timestamp: new Date(value.timestamp).toLocaleString(),
            size: JSON.stringify(value.data).length
        }));
        
        return {
            totalEntries: this.cache.size,
            entries: entries,
            totalSize: entries.reduce((acc, entry) => acc + entry.size, 0)
        };
    }
    
    /**
     * Simulación de datos para desarrollo
     */
    generateMockData(type = 'empresas', count = 12) {
        const mockData = [];
        const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 
                       'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        for (let i = 0; i < count; i++) {
            let label, baseValue;
            
            switch (type) {
                case 'empresas':
                    label = months[i % 12];
                    baseValue = 50 + Math.random() * 50;
                    break;
                case 'usuarios':
                    label = months[i % 12];
                    baseValue = 100 + Math.random() * 100;
                    break;
                case 'eventos':
                    label = months[i % 12];
                    baseValue = 5 + Math.random() * 15;
                    break;
                default:
                    label = `Item ${i + 1}`;
                    baseValue = Math.random() * 100;
            }
            
            mockData.push({
                label: label,
                valor: Math.round(baseValue)
            });
        }
        
        return {
            data: mockData,
            metadata: {
                fuente: type,
                total_puntos: count,
                ultimo_valor: mockData[mockData.length - 1]?.valor || 0,
                timestamp: new Date().toISOString()
            }
        };
    }
}

// Hacer disponible globalmente
window.ClústerGraficosData = ClústerGraficosData;