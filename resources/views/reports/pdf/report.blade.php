<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->name }}</title>
    <style>
        @page {
            margin: 8mm;
            size: A4;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* Portada */
        .cover-page {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px;
            box-sizing: border-box;
        }
        
        .cover-logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .cover-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .cover-subtitle {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .cover-info {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            margin-bottom: 40px;
        }
        
        .cover-info-item {
            margin: 10px 0;
            font-size: 16px;
        }
        
        .cover-footer {
            font-size: 14px;
            opacity: 0.8;
            margin-top: auto;
        }
        
        /* Página de resumen */
        .summary-page {
            page-break-before: always;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            margin: 0;
            font-weight: bold;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #667eea;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .summary-section {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 2px solid #2196f3;
        }
        
        .summary-title {
            font-size: 18px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .summary-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #e3f2fd;
        }
        
        .summary-number {
            font-size: 20px;
            font-weight: bold;
            color: #2196f3;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }
        
        .fan-page-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .fan-page-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .fan-page-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        
        .fan-page-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .fan-page-stat {
            text-align: center;
        }
        
        .fan-page-stat-number {
            font-size: 20px;
            font-weight: bold;
            display: block;
        }
        
        .fan-page-stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .ad-page {
            page-break-before: always;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .ad-card {
            background: white;
            border: 3px solid #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .ad-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .ad-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            margin-bottom: 8px;
        }
        
        .ad-id {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .ad-content {
            display: flex;
            flex-direction: column;
            padding: 25px;
            flex: 1;
            align-items: center;
        }
        
        .ad-image {
            width: 60%;
            height: 350px;
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed #dee2e6;
            overflow: hidden;
            margin: 0 auto 20px auto;
        }
        
        .ad-image img {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            object-fit: cover;
            max-width: 100%;
            max-height: 100%;
        }
        
        .ad-image-placeholder {
            color: #6c757d;
            font-size: 16px;
            text-align: center;
            padding: 20px;
        }
        
        .ad-metrics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            align-content: start;
            width: 100%;
            max-width: 600px;
        }
        
        .metric-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            border-left: 3px solid #667eea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .metric-label {
            font-weight: bold;
            color: #495057;
            font-size: 11px;
        }
        
        .metric-value {
            color: #2196f3;
            font-weight: bold;
            font-size: 13px;
        }
        
        .footer {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            border-top: 3px solid #667eea;
        }
        
        .footer-text {
            color: #6c757d;
            font-size: 12px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .brand-separator-page {
            page-break-before: always;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            text-align: center;
            padding: 40px;
            box-sizing: border-box;
        }
        
        .brand-logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .brand-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .brand-subtitle {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .brand-stats {
            background: rgba(255,255,255,0.15);
            padding: 25px;
            border-radius: 15px;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .brand-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .brand-stat {
            text-align: center;
        }
        
        .brand-stat-number {
            font-size: 24px;
            font-weight: bold;
            display: block;
            margin-bottom: 3px;
        }
        
        .brand-stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .highlight {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .success {
            color: #28a745;
        }
        
        .warning {
            color: #ffc107;
        }
        
        .danger {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Portada -->
    <div class="cover-page">
        <div class="cover-logo">📊</div>
        <h1 class="cover-title">{{ $report->name }}</h1>
        <div class="cover-subtitle">Reporte de Campañas de Facebook</div>
        
        <div class="cover-info">
            <div class="cover-info-item">📅 Período: {{ $period['start'] }} - {{ $period['end'] }}</div>
            <div class="cover-info-item">🏢 Fan Pages: {{ count($facebook_data['fan_pages']) }}</div>
            <div class="cover-info-item">📊 Total Anuncios: {{ number_format($facebook_data['total_ads']) }}</div>
            <div class="cover-info-item">👥 Alcance Total: {{ number_format($facebook_data['total_reach']) }}</div>
        </div>
        
        <div class="cover-footer">
            Generado el {{ $generated_at }}<br>
            Sistema de Automatización de Facebook Ads
        </div>
    </div>
    
    <!-- Página de Resumen -->
    <div class="summary-page">
        <div class="page-header">
            <h1 class="page-title">📊 Resumen General</h1>
        </div>
        
        <!-- Información del Reporte -->
        <div class="info-section">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Período:</span>
                    <span class="info-value">{{ $period['start'] }} - {{ $period['end'] }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Generado:</span>
                    <span class="info-value">{{ $generated_at }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <span class="info-value success">Completado</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fan Pages:</span>
                    <span class="info-value">{{ count($facebook_data['fan_pages']) }}</span>
                </div>
            </div>
        </div>
        
        <!-- Resumen General -->
        <div class="summary-section">
            <div class="summary-title">📈 Métricas Principales</div>
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-number">{{ number_format($facebook_data['total_ads']) }}</div>
                    <div class="summary-label">Total Anuncios</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number">{{ number_format($facebook_data['total_reach']) }}</div>
                    <div class="summary-label">Alcance Total</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number">{{ number_format($facebook_data['total_impressions']) }}</div>
                    <div class="summary-label">Impresiones</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number">{{ number_format($facebook_data['total_clicks']) }}</div>
                    <div class="summary-label">Clicks</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number">${{ number_format($facebook_data['total_spend'], 2) }}</div>
                    <div class="summary-label">Gasto Total</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number">{{ number_format(($facebook_data['total_clicks'] / max($facebook_data['total_impressions'], 1)) * 100, 2) }}%</div>
                    <div class="summary-label">CTR Promedio</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Anuncios organizados por marca -->
    @foreach($facebook_data['fan_pages'] as $index => $fanPage)
        <!-- Página de separación de marca -->
        <div style="page-break-before: always; background: #ff6b6b; color: white; text-align: center; padding: 100px 40px; min-height: 600px;">
            <div style="font-size: 48px; margin-bottom: 30px;">🏢</div>
            <h1 style="font-size: 36px; font-weight: bold; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">{{ $fanPage['page_name'] }}</h1>
            <div style="font-size: 18px; opacity: 0.9; margin-bottom: 40px;">Fan Page - Resumen de Campañas</div>
            
            <div style="background: white; color: #333; padding: 30px; border-radius: 15px; margin-top: 30px; display: inline-block;">
                <div style="font-size: 20px; font-weight: bold; margin-bottom: 20px;">Estadísticas de la Fan Page</div>
                <div style="font-size: 16px; margin-bottom: 10px;">📊 Anuncios: {{ number_format($fanPage['total_ads']) }}</div>
                <div style="font-size: 16px; margin-bottom: 10px;">👥 Alcance: {{ number_format($fanPage['total_reach']) }}</div>
                <div style="font-size: 16px; margin-bottom: 10px;">👁️ Impresiones: {{ number_format($fanPage['total_impressions']) }}</div>
                <div style="font-size: 16px; margin-bottom: 10px;">💰 Gasto: ${{ number_format($fanPage['total_spend'], 2) }}</div>
            </div>
        </div>
        
        <!-- Anuncios de esta marca -->
        @foreach($fanPage['ads'] as $ad)
            <div class="ad-page">
                <div class="ad-card">
                    <div class="ad-header">
                        <h2 class="ad-title">{{ $ad['ad_name'] }}</h2>
                        <div class="ad-id">ID: {{ $ad['ad_id'] }} | {{ $fanPage['page_name'] }}</div>
                    </div>
                    
                    <div class="ad-content">
                        <div class="ad-image">
                            @if(!empty($ad['ad_image_url']))
                                <img src="{{ $ad['ad_image_url'] }}" alt="Imagen del anuncio">
                            @else
                                <div class="ad-image-placeholder">
                                    📱<br>
                                    Sin imagen<br>
                                    disponible
                                </div>
                            @endif
                        </div>
                        
                        <div class="ad-metrics">
                            <div class="metric-item">
                                <span class="metric-label">Alcance:</span>
                                <span class="metric-value">{{ number_format($ad['reach']) }}</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">Impresiones:</span>
                                <span class="metric-value">{{ number_format($ad['impressions']) }}</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">Clicks:</span>
                                <span class="metric-value">{{ number_format($ad['clicks']) }}</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">CTR:</span>
                                <span class="metric-value">{{ number_format($ad['ctr'], 2) }}%</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">CPM:</span>
                                <span class="metric-value">${{ number_format($ad['cpm'], 2) }}</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">CPC:</span>
                                <span class="metric-value">${{ number_format($ad['cpc'], 2) }}</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">Frecuencia:</span>
                                <span class="metric-value">{{ number_format($ad['frequency'], 2) }}</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-label">Gasto:</span>
                                <span class="metric-value">${{ number_format($ad['spend'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach
    
    <!-- Pie de página -->
    <div class="footer">
        <div class="footer-text">
            Reporte generado automáticamente por el sistema de automatización de Facebook Ads<br>
            {{ $generated_at }} | {{ $report->name }}
        </div>
    </div>
</body>
</html>
