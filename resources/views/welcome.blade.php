<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('landing.s.cekat_biz_id_ai_chatbot_kustom_untuk_data_anda') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Memuat Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Memuat React, ReactDOM, Babel (untuk JSX in-browser), dan React Flow -->
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <!-- Kompatibilitas: build UMD @xyflow/react membutuhkan global `jsxRuntime` (react/jsx-runtime) -->
    <script>
        window.jsxRuntime = {
            Fragment: React.Fragment,
            jsx: function (type, props, key) {
                return React.createElement(type, key === undefined ? props : Object.assign({}, props, { key: key }));
            },
            jsxs: function (type, props, key) {
                return window.jsxRuntime.jsx(type, props, key);
            }
        };
    </script>
    <!-- React Flow CSS & JS UMD -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xyflow/react@12.3.6/dist/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/@xyflow/react@12.3.6/dist/umd/index.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class', // Mendukung toggle manual
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                            950: '#042f2e',
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.6s ease-out forwards',
                        'icon-bounce': 'iconBounce 2s infinite',
                        'icon-wiggle': 'iconWiggle 1s ease-in-out infinite',
                        'icon-pulse': 'iconPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(15px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        iconBounce: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-15%)' },
                        },
                        iconWiggle: {
                            '0%, 100%': { transform: 'rotate(-5deg)' },
                            '50%': { transform: 'rotate(5deg)' },
                        },
                        iconPulse: {
                            '0%, 100%': { opacity: 1, transform: 'scale(1)' },
                            '50%': { opacity: .7, transform: 'scale(0.9)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .typing-cursor::after {
            content: '|';
            animation: blink 1s step-end infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent; 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #475569; 
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #64748b; 
        }
        
        /* Utility untuk ukuran Lucide Icons yang konsisten */
        .lucide {
            width: 1.25em;
            height: 1.25em;
            stroke-width: 2;
        }

        .rag-flow-shell {
            height: 460px;
            min-height: 460px;
        }

        .rag-flow-stage {
            position: relative;
            overflow: hidden;
            --rag-fade-bg: #020617;
        }

        .rag-flow-stage::before,
        .rag-flow-stage::after {
            content: '';
            display: none;
            position: absolute;
            top: -1px;
            bottom: -1px;
            z-index: 7;
            width: clamp(110px, 18vw, 280px);
            pointer-events: none;
        }

        .rag-flow-stage::before {
            left: 0;
            background: linear-gradient(
                90deg,
                var(--rag-fade-bg) 0%,
                color-mix(in srgb, var(--rag-fade-bg) 98%, transparent) 18%,
                color-mix(in srgb, var(--rag-fade-bg) 84%, transparent) 38%,
                color-mix(in srgb, var(--rag-fade-bg) 52%, transparent) 62%,
                color-mix(in srgb, var(--rag-fade-bg) 20%, transparent) 82%,
                transparent 100%
            );
        }

        .rag-flow-stage::after {
            right: 0;
            background: linear-gradient(
                270deg,
                var(--rag-fade-bg) 0%,
                color-mix(in srgb, var(--rag-fade-bg) 98%, transparent) 18%,
                color-mix(in srgb, var(--rag-fade-bg) 84%, transparent) 38%,
                color-mix(in srgb, var(--rag-fade-bg) 52%, transparent) 62%,
                color-mix(in srgb, var(--rag-fade-bg) 20%, transparent) 82%,
                transparent 100%
            );
        }

        .rag-node {
            width: 275px;
            min-height: 195px;
            border-radius: 18px;
            border: 1px solid rgba(51, 65, 85, 0.9);
            background: linear-gradient(155deg, rgba(15, 23, 42, 0.97), rgba(2, 6, 23, 0.99));
            color: #e2e8f0;
            box-shadow: 0 20px 60px rgba(2, 6, 23, 0.6);
            padding: 15px;
            transition: transform 500ms cubic-bezier(0.2, 0.8, 0.2, 1), border-color 500ms ease, box-shadow 500ms ease, opacity 500ms ease;
            overflow: hidden;
            position: relative;
            backdrop-filter: blur(12px);
        }

        .rag-node.is-active {
            transform: scale(1.06);
            border-color: var(--node-accent, #14b8a6);
            box-shadow: 0 25px 70px rgba(20, 184, 166, 0.22), 0 0 0 1px color-mix(in srgb, var(--node-accent, #14b8a6) 75%, transparent);
        }

        .rag-node.is-complete {
            border-color: rgba(45, 212, 191, 0.45);
            box-shadow: 0 15px 45px rgba(2, 6, 23, 0.5);
        }

        .rag-node-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.6);
        }

        .rag-node-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--node-accent, #14b8a6) 18%, transparent);
            color: var(--node-accent, #14b8a6);
            flex: 0 0 auto;
            font-size: 15px;
            font-weight: 700;
            border: 1px solid color-mix(in srgb, var(--node-accent, #14b8a6) 35%, transparent);
        }

        .rag-node-title {
            font-size: 13px;
            line-height: 1.25;
            font-weight: 800;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rag-node-subtitle {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
        }

        .rag-input-box {
            border: 1px solid rgba(71, 85, 105, 0.9);
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.95);
            padding: 10px 12px;
            font-size: 12px;
            color: #cbd5e1;
            min-height: 68px;
            line-height: 1.45;
            position: relative;
            transition: border-color 300ms ease, box-shadow 300ms ease;
        }

        .rag-input-box.is-clicked {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.18), 0 4px 16px rgba(56, 189, 248, 0.1);
        }

        .rag-send-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #38bdf8;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 200ms ease, background-color 200ms ease;
        }

        .rag-send-btn.is-active {
            transform: scale(0.92);
            background: #0284c7;
            box-shadow: 0 0 12px rgba(56, 189, 248, 0.6);
        }

        .rag-caret::after {
            content: '|';
            color: #38bdf8;
            animation: blink 1s step-end infinite;
            font-weight: 700;
        }

        .rag-kb-list {
            display: grid;
            gap: 8px;
            position: relative;
        }

        .rag-kb-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
            border: 1px solid rgba(51, 65, 85, 0.85);
            border-radius: 10px;
            padding: 7px 9px;
            font-size: 11px;
            color: #cbd5e1;
            background: rgba(15, 23, 42, 0.7);
            opacity: 0.42;
            transform: translateY(4px);
            transition: opacity 350ms ease, transform 350ms ease, border-color 350ms ease, box-shadow 350ms ease;
            position: relative;
            overflow: hidden;
        }

        .rag-kb-item.is-found {
            opacity: 1;
            transform: translateY(0);
            border-color: rgba(20, 184, 166, 0.75);
            background: rgba(15, 23, 42, 0.95);
            box-shadow: 0 4px 18px rgba(20, 184, 166, 0.15);
        }

        .rag-kb-item.is-dimmed {
            opacity: 0.28;
            filter: grayscale(0.5);
        }

        .rag-kb-highlight {
            background: rgba(20, 184, 166, 0.2);
            color: #5eead4;
            border-radius: 4px;
            padding: 1px 4px;
            font-weight: 600;
            animation: highlightGlow 2s ease-in-out infinite alternate;
        }

        @keyframes highlightGlow {
            0% { background: rgba(20, 184, 166, 0.15); color: #5eead4; }
            100% { background: rgba(20, 184, 166, 0.35); color: #ccfbf1; text-shadow: 0 0 8px rgba(45, 212, 191, 0.5); }
        }

        .rag-scanner-sweep {
            position: absolute;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #2dd4bf 50%, transparent);
            box-shadow: 0 0 10px #2dd4bf, 0 0 20px #14b8a6;
            z-index: 5;
            pointer-events: none;
            animation: scanRadar 1.8s ease-in-out infinite;
        }

        @keyframes scanRadar {
            0% { top: 0; opacity: 0; }
            15% { opacity: 1; }
            85% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .rag-reasoning-step {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 5px 8px;
            border-radius: 8px;
            background: rgba(30, 41, 59, 0.6);
            font-size: 10.5px;
            color: #94a3b8;
            border: 1px solid rgba(51, 65, 85, 0.5);
            transition: all 300ms ease;
        }

        .rag-reasoning-step.is-done {
            background: rgba(168, 85, 247, 0.12);
            border-color: rgba(168, 85, 247, 0.4);
            color: #e9d5ff;
        }

        .rag-output-bubble {
            border-radius: 14px 14px 14px 4px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.4);
            padding: 10px 12px;
            color: #d1fae5;
            font-size: 11.5px;
            line-height: 1.45;
            min-height: 74px;
            position: relative;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.08);
        }

        .rag-flow-cursor {
            position: absolute;
            left: 0;
            top: 0;
            z-index: 30;
            width: 28px;
            height: 28px;
            color: #f8fafc;
            filter: drop-shadow(0 10px 18px rgba(2, 6, 23, 0.75));
            transform: translate3d(var(--cursor-x, 112px), var(--cursor-y, 194px), 0);
            transition: transform 900ms cubic-bezier(0.22, 1, 0.36, 1), opacity 420ms ease;
            pointer-events: none;
        }

        .rag-flow-cursor::after {
            content: '';
            position: absolute;
            left: 14px;
            top: 14px;
            width: 14px;
            height: 14px;
            border-radius: 999px;
            border: 2px solid #38bdf8;
            opacity: 0;
            transform: scale(0.3);
        }

        .rag-flow-cursor.is-clicking::after {
            animation: ragClickRing 750ms ease-out;
        }

        @keyframes ragClickRing {
            0% { opacity: 1; transform: scale(0.3); border-color: #38bdf8; }
            100% { opacity: 0; transform: scale(3.2); border-color: #0284c7; }
        }

        .react-flow__edge-path.rag-edge-active,
        .rag-edge-active .react-flow__edge-path {
            filter: drop-shadow(0 0 9px currentColor);
            stroke-dasharray: 8 6;
            animation: ragEdgeMove 1200ms linear infinite;
        }

        @keyframes ragEdgeMove {
            to { stroke-dashoffset: -28; }
        }

        .rag-flow-canvas {
            background: #020617;
            -webkit-mask-image: linear-gradient(
                90deg,
                transparent 0%,
                rgba(0, 0, 0, 0.2) 6%,
                #000 18%,
                #000 82%,
                rgba(0, 0, 0, 0.2) 94%,
                transparent 100%
            );
            mask-image: linear-gradient(
                90deg,
                transparent 0%,
                rgba(0, 0, 0, 0.2) 6%,
                #000 18%,
                #000 82%,
                rgba(0, 0, 0, 0.2) 94%,
                transparent 100%
            );
        }

        html:not(.dark) #alur-rag {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 46%, #ffffff 100%);
        }

        html:not(.dark) .rag-flow-shell,
        html:not(.dark) .rag-flow-canvas {
            background: transparent;
        }

        html:not(.dark) .rag-flow-stage {
            --rag-fade-bg: #f8fafc;
        }

        html:not(.dark) .rag-node {
            border-color: rgba(203, 213, 225, 0.95);
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.98));
            color: #334155;
            box-shadow: 0 18px 52px rgba(15, 23, 42, 0.12);
        }

        html:not(.dark) .rag-node.is-active {
            border-color: var(--node-accent, #14b8a6);
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.14), 0 0 0 1px color-mix(in srgb, var(--node-accent, #14b8a6) 50%, transparent);
        }

        html:not(.dark) .rag-node.is-complete {
            border-color: rgba(20, 184, 166, 0.35);
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.1);
        }

        html:not(.dark) .rag-node-header {
            border-bottom-color: rgba(203, 213, 225, 0.9);
        }

        html:not(.dark) .rag-node-title {
            color: #0f172a;
        }

        html:not(.dark) .rag-node-subtitle {
            color: #64748b;
        }

        html:not(.dark) .rag-input-box {
            border-color: rgba(203, 213, 225, 0.95);
            background: rgba(255, 255, 255, 0.96);
            color: #334155;
        }

        html:not(.dark) .rag-kb-item {
            border-color: rgba(203, 213, 225, 0.9);
            background: rgba(255, 255, 255, 0.74);
            color: #475569;
        }

        html:not(.dark) .rag-kb-item.is-found {
            border-color: rgba(20, 184, 166, 0.65);
            background: rgba(240, 253, 250, 0.92);
            box-shadow: 0 8px 24px rgba(20, 184, 166, 0.12);
        }

        html:not(.dark) .rag-kb-highlight {
            background: rgba(20, 184, 166, 0.14);
            color: #0f766e;
        }

        html:not(.dark) .rag-scanner-sweep {
            background: linear-gradient(90deg, transparent, #0d9488 50%, transparent);
            box-shadow: 0 0 10px rgba(13, 148, 136, 0.55), 0 0 20px rgba(20, 184, 166, 0.35);
        }

        html:not(.dark) .rag-reasoning-step {
            background: rgba(248, 250, 252, 0.86);
            border-color: rgba(203, 213, 225, 0.9);
            color: #64748b;
        }

        html:not(.dark) .rag-reasoning-step.is-done {
            background: rgba(168, 85, 247, 0.1);
            border-color: rgba(168, 85, 247, 0.3);
            color: #6b21a8;
        }

        html:not(.dark) .rag-output-bubble {
            background: rgba(236, 253, 245, 0.92);
            border-color: rgba(16, 185, 129, 0.38);
            color: #065f46;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.12);
        }

        html:not(.dark) .rag-flow-cursor {
            color: #0f172a;
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.24));
        }

        html:not(.dark) .rag-node .text-slate-200,
        html:not(.dark) .rag-node .text-emerald-100 {
            color: #334155 !important;
        }

        html:not(.dark) .rag-node .text-slate-400,
        html:not(.dark) .rag-node .text-slate-500 {
            color: #64748b !important;
        }

        html:not(.dark) .rag-node .text-slate-600 {
            color: #94a3b8 !important;
        }

        html:not(.dark) .rag-node .bg-slate-900\/90,
        html:not(.dark) .rag-node .bg-slate-900\/80 {
            background-color: rgba(255, 255, 255, 0.82) !important;
        }

        html:not(.dark) .rag-node .bg-slate-800 {
            background-color: #e2e8f0 !important;
        }

        html:not(.dark) .rag-node .border-slate-800,
        html:not(.dark) .rag-node .border-slate-700\/50,
        html:not(.dark) .rag-node .border-slate-700\/60 {
            border-color: rgba(203, 213, 225, 0.9) !important;
        }

        html:not(.dark) .rag-node .bg-slate-500 {
            background-color: #94a3b8 !important;
        }

        @media (max-width: 768px) {
            .rag-flow-shell {
                height: 420px;
                min-height: 420px;
            }

            .rag-flow-stage {
                margin-left: -1rem;
                margin-right: -1rem;
            }

            .rag-flow-stage::before,
            .rag-flow-stage::after {
                width: 96px;
            }

            .rag-flow-canvas {
                -webkit-mask-image: linear-gradient(
                    90deg,
                    transparent 0%,
                    rgba(0, 0, 0, 0.18) 8%,
                    #000 26%,
                    #000 74%,
                    rgba(0, 0, 0, 0.18) 92%,
                    transparent 100%
                );
                mask-image: linear-gradient(
                    90deg,
                    transparent 0%,
                    rgba(0, 0, 0, 0.18) 8%,
                    #000 26%,
                    #000 74%,
                    rgba(0, 0, 0, 0.18) 92%,
                    transparent 100%
                );
            }

            .rag-node {
                width: 245px;
                min-height: 185px;
                padding: 12px;
            }

            .rag-flow-cursor {
                width: 24px;
                height: 24px;
            }
        }
    </style>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
            // Update ikon tema dengan delay singkat agar transisi terlihat
            setTimeout(() => {
                updateThemeIcons();
            }, 10);
        }

        function updateThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            document.querySelectorAll('.theme-icon-dark').forEach(el => el.style.display = isDark ? 'block' : 'none');
            document.querySelectorAll('.theme-icon-light').forEach(el => el.style.display = isDark ? 'none' : 'block');
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-slate-950 dark:text-gray-100 antialiased selection:bg-brand-500 selection:text-white transition-colors duration-300 relative overflow-x-hidden">

    <!-- Background Elements (Clean Grid) -->
    <div class="fixed inset-0 z-[-1] pointer-events-none">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px] dark:bg-[linear-gradient(to_right,#ffffff0a_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0a_1px,transparent_1px)]"></div>
        <div class="absolute inset-x-0 top-0 h-96 bg-gradient-to-b from-brand-50/50 to-transparent dark:from-brand-950/20 dark:to-transparent"></div>
    </div>

    <nav class="fixed w-full z-50 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md border-b border-gray-200 dark:border-slate-800 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2 cursor-pointer group">
                    <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center transition-transform group-hover:scale-105 group-hover:animate-icon-wiggle">
                        <i data-lucide="bot" class="text-white w-5 h-5"></i>
                    </div>
                    <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">Cekat<span class="text-brand-600 dark:text-brand-400">.biz.id</span></span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="flex items-baseline space-x-6">
                        <a href="#fitur" class="hover:text-brand-600 dark:hover:text-brand-400 text-gray-600 dark:text-gray-300 px-3 py-2 text-sm font-medium transition-colors">{{ __('docs.s.fitur') }}</a>
                        <a href="#cara-kerja" class="hover:text-brand-600 dark:hover:text-brand-400 text-gray-600 dark:text-gray-300 px-3 py-2 text-sm font-medium transition-colors">{{ __('docs.s.cara_kerja') }}</a>
                        <a href="#harga" class="hover:text-brand-600 dark:hover:text-brand-400 text-gray-600 dark:text-gray-300 px-3 py-2 text-sm font-medium transition-colors">{{ __('docs.s.harga') }}</a>
                    </div>
                </div>

                <div class="hidden md:flex items-center gap-4">
                    <!-- Theme Toggle Button -->
                    <button onclick="toggleTheme()" class="group text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-800 p-2 rounded-lg transition-colors focus:outline-none">
                        <div class="theme-icon-dark hidden group-hover:text-brand-400 group-hover:animate-icon-pulse"><i data-lucide="moon" class="w-4 h-4"></i></div>
                        <div class="theme-icon-light block group-hover:text-brand-600 group-hover:animate-icon-pulse"><i data-lucide="sun" class="w-4 h-4"></i></div>
                    </button>
                    
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}" class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-gray-100 px-4 py-2 rounded-md text-sm font-medium transition-colors group flex items-center gap-2">
                            {{ __('general.s.dashboard') }}
                            <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                        </a>
                    @else
                        <a href="/login" class="text-gray-600 dark:text-gray-300 hover:text-slate-900 dark:hover:text-white font-medium text-sm transition-colors">{{ __('auth.s.masuk') }}</a>
                        <a href="/register" class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-gray-100 px-4 py-2 rounded-md text-sm font-medium transition-colors group flex items-center gap-2">
                            {{ __('landing.s.buat_bot_anda') }}
                            <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center gap-2">
                    <button onclick="toggleTheme()" class="text-gray-500 dark:text-gray-400 p-2 focus:outline-none">
                        <div class="theme-icon-dark hidden"><i data-lucide="moon" class="w-5 h-5"></i></div>
                        <div class="theme-icon-light block"><i data-lucide="sun" class="w-5 h-5"></i></div>
                    </button>
                    <button type="button" class="text-gray-600 dark:text-gray-300 hover:text-slate-900 dark:hover:text-white focus:outline-none p-2" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden bg-white dark:bg-slate-950 border-t border-gray-200 dark:border-slate-800" id="mobile-menu">
            <div class="px-4 pt-2 pb-4 space-y-1">
                <a href="#fitur" class="text-gray-600 dark:text-gray-300 block py-2 text-base font-medium">{{ __('docs.s.fitur') }}</a>
                <a href="#cara-kerja" class="text-gray-600 dark:text-gray-300 block py-2 text-base font-medium">{{ __('docs.s.cara_kerja') }}</a>
                <a href="#harga" class="text-gray-600 dark:text-gray-300 block py-2 text-base font-medium">{{ __('docs.s.harga') }}</a>
                <div class="pt-4 flex flex-col gap-3 border-t border-gray-100 dark:border-slate-800">
                    @auth
                        <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}" class="text-center bg-slate-900 dark:bg-white text-white dark:text-slate-900 py-2 rounded-md text-base font-medium flex items-center justify-center gap-2">
                            {{ __('general.s.dashboard') }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    @else
                        <a href="/login" class="text-center text-gray-600 dark:text-gray-300 py-2 border border-gray-300 dark:border-slate-700 rounded-md text-base font-medium">{{ __('auth.s.masuk') }}</a>
                        <a href="/register" class="text-center bg-slate-900 dark:bg-white text-white dark:text-slate-900 py-2 rounded-md text-base font-medium flex items-center justify-center gap-2">
                            {{ __('landing.s.buat_bot_anda') }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <section class="pt-32 pb-16 lg:pt-40 lg:pb-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-12 lg:gap-12 items-center">
                
                <!-- Hero Text -->
                <div class="lg:col-span-6 text-center lg:text-left mb-12 lg:mb-0 opacity-0 animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400 border border-brand-100 dark:border-brand-800/50 text-sm font-medium mb-6">
                        <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                        {{ __('landing.s.didukung_teknologi_rag_ai_generatif') }}
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6 text-slate-900 dark:text-white leading-[1.1]">
                        {{ __('landing.s.ubah_data_anda_menjadi') }} <br class="hidden lg:block"/>
                        <span class="text-brand-600 dark:text-brand-400">{{ __('landing.s.ai_chatbot_cerdas') }}</span>
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                        {{ __('landing.s.unggah_dokumen_tautan_website_atau_basis_pengeta') }}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        @auth
                            <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}" class="group bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                {{ __('landing.s.mulai_gratis') }} <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                            </a>
                        @else
                            <a href="/register" class="group bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                                {{ __('landing.s.mulai_gratis') }} <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                            </a>
                        @endauth
                        <a href="#cara-kerja" class="group bg-white dark:bg-slate-900 text-slate-900 dark:text-white border border-gray-300 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 shadow-sm">
                            <i data-lucide="play" class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover:text-brand-500 transition-colors"></i> {{ __('landing.s.lihat_demo') }}
                        </a>
                    </div>
                    <div class="mt-8 flex items-center justify-center lg:justify-start gap-6 text-sm text-gray-500 dark:text-gray-400 font-medium">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-brand-500"></i> {{ __('landing.s.tanpa_perlu_coding') }}
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-brand-500"></i> {{ __('landing.s.mudah_disematkan_2') }}
                        </div>
                    </div>
                </div>

                <!-- Hero Interactive Chat Simulation -->
                <div class="lg:col-span-6 relative lg:pl-10 opacity-0 animate-fade-in-up" style="animation-delay: 0.2s;">
                    <div class="relative rounded-xl bg-white dark:bg-slate-900 overflow-hidden shadow-xl border border-gray-200 dark:border-slate-800 flex flex-col h-[400px]">
                        <!-- Chat Header -->
                        <div class="bg-gray-50 dark:bg-slate-950 px-5 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-brand-100 dark:bg-brand-900/50 flex items-center justify-center text-brand-600 dark:text-brand-400 relative">
                                <i data-lucide="bot" class="w-5 h-5 animate-icon-wiggle"></i>
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white text-sm">{{ __('landing.s.asisten_ai_cekat') }}</h3>
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('landing.s.sedang_membalas') }}
                                </div>
                            </div>
                        </div>

                        <!-- Chat Body -->
                        <div class="p-5 flex-1 overflow-y-auto flex flex-col gap-5 bg-white dark:bg-slate-900 scroll-smooth" id="chat-simulation">
                            <!-- Initial Bot Message -->
                            <div class="flex gap-3 max-w-[85%]">
                                <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-900/50 flex-shrink-0 flex items-center justify-center mt-1 text-brand-600 dark:text-brand-400">
                                    <i data-lucide="bot" class="w-4 h-4"></i>
                                </div>
                                <div class="bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-gray-200 p-3.5 rounded-2xl rounded-tl-none text-sm border border-gray-200 dark:border-slate-700">
                                    {{ __('landing.s.halo_saya_telah_dilatih_menggunakan_dokumentasi') }}
                                </div>
                            </div>
                            <!-- Pesan dinamis akan dimuat oleh JS -->
                        </div>

                        <!-- Chat Input -->
                        <div class="p-4 bg-gray-50 dark:bg-slate-950 border-t border-gray-200 dark:border-slate-800">
                            <div class="relative group">
                                <input type="text" id="hero-chat-input" aria-label="{{ __('landing.s.tanyakan_sesuatu') }}" placeholder="{{ __('landing.s.tanyakan_sesuatu_2') }}" class="w-full bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-700 rounded-lg py-2.5 pl-4 pr-12 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 text-slate-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm" disabled>
                                <button class="absolute right-2 top-1.5 w-8 h-8 bg-brand-600 rounded-md flex items-center justify-center text-white opacity-50 cursor-not-allowed transition-colors">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Clean Floating Badge -->
                    <div class="absolute -bottom-5 -left-5 bg-white dark:bg-slate-800 p-3 rounded-lg flex items-center gap-3 shadow-lg border border-gray-200 dark:border-slate-700 group cursor-default">
                        <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-500/10 rounded-md flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-500/20 transition-colors">
                            <i data-lucide="file-text" class="w-5 h-5 group-hover:animate-icon-bounce"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">{{ __('landing.s.dilatih_menggunakan') }}</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ __('landing.s.1_240_dokumen') }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="py-10 border-y border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-950/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mb-6 tracking-widest uppercase">{{ __('landing.s.mendukung_otomatisasi_untuk_tim_inovatif') }}</p>
            <div class="flex flex-wrap justify-center gap-8 md:gap-16 opacity-60 grayscale hover:grayscale-0 transition-all duration-300">
                <div class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-gray-300 group"><i data-lucide="cloud" class="w-6 h-6 group-hover:text-blue-500 transition-colors"></i> CloudServe</div>
                <div class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-gray-300 group"><i data-lucide="hash" class="w-6 h-6 group-hover:text-purple-500 transition-colors"></i> TeamSync</div>
                <div class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-gray-300 group"><i data-lucide="credit-card" class="w-6 h-6 group-hover:text-indigo-500 transition-colors"></i> PayFlow</div>
                <div class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-gray-300 group"><i data-lucide="code-2" class="w-6 h-6 group-hover:text-gray-900 dark:group-hover:text-white transition-colors"></i> CodeForge</div>
            </div>
        </div>
    </section>

    <section id="fitur" class="py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-brand-600 dark:text-brand-400 font-bold tracking-wider uppercase text-xs mb-3">{{ __('landing.s.mengapa_memilih_cekat') }}</h2>
                <h3 class="text-3xl md:text-4xl font-extrabold mb-6 text-slate-900 dark:text-white">{{ __('landing.s.semua_yang_anda_butuhkan_untuk_membangun_agen_ai') }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-lg">{{ __('landing.s.kami_menyederhanakan_proses_kompleks_integrasi_l') }}</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Feature 1 -->
                <div class="group bg-white dark:bg-slate-900 p-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center mb-6 text-blue-600 dark:text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20 transition-colors">
                        <i data-lucide="database" class="w-6 h-6 group-hover:animate-icon-bounce"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3 text-slate-900 dark:text-white">{{ __('landing.s.integrasi_multi_sumber') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.unggah_pdf_dokumen_word_teks_atau_cukup_salin_ta') }}</p>
                </div>

                <!-- Feature 2 -->
                <div class="group bg-white dark:bg-slate-900 p-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-lg bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center mb-6 text-brand-600 dark:text-brand-400 group-hover:bg-brand-100 dark:group-hover:bg-brand-500/20 transition-colors">
                        <i data-lucide="wand-2" class="w-6 h-6 group-hover:animate-icon-wiggle"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3 text-slate-900 dark:text-white">{{ __('landing.s.prompting_lanjutan') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.kendalikan_persona_bot_anda_berikan_instruksi_un') }}</p>
                </div>

                <!-- Feature 3 -->
                <div class="group bg-white dark:bg-slate-900 p-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-lg bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center mb-6 text-purple-600 dark:text-purple-400 group-hover:bg-purple-100 dark:group-hover:bg-purple-500/20 transition-colors">
                        <i data-lucide="code" class="w-6 h-6 group-hover:animate-icon-pulse"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3 text-slate-900 dark:text-white">{{ __('landing.s.mudah_disematkan') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.salin_dan_tempel_satu_baris_kode_javascript_untu') }}</p>
                </div>

                <!-- Feature 4 -->
                <div class="group bg-white dark:bg-slate-900 p-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-lg bg-orange-50 dark:bg-orange-500/10 flex items-center justify-center mb-6 text-orange-600 dark:text-orange-400 group-hover:bg-orange-100 dark:group-hover:bg-orange-500/20 transition-colors">
                        <i data-lucide="line-chart" class="w-6 h-6 group-hover:animate-icon-bounce"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3 text-slate-900 dark:text-white">{{ __('landing.s.dasbor_analitik') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.pantau_pertanyaan_pengguna_anda_lacak_tingkat_pe') }}</p>
                </div>

                <!-- Feature 5 -->
                <div class="group bg-white dark:bg-slate-900 p-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-lg bg-pink-50 dark:bg-pink-500/10 flex items-center justify-center mb-6 text-pink-600 dark:text-pink-400 group-hover:bg-pink-100 dark:group-hover:bg-pink-500/20 transition-colors">
                        <i data-lucide="globe" class="w-6 h-6 group-hover:animate-spin"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3 text-slate-900 dark:text-white">{{ __('landing.s.dukungan_multi_bahasa') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.berikan_dukungan_secara_global_ai_kami_secara_ot') }}</p>
                </div>

                <!-- Feature 6 -->
                <div class="group bg-white dark:bg-slate-900 p-8 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all hover:-translate-y-1">
                    <div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-6 text-slate-600 dark:text-slate-300 group-hover:bg-slate-200 dark:group-hover:bg-slate-700 transition-colors">
                        <i data-lucide="shield-check" class="w-6 h-6 group-hover:animate-icon-pulse"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-3 text-slate-900 dark:text-white">{{ __('landing.s.keamanan_kelas_perusahaan') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.data_anda_tetap_milik_anda_kami_menggunakan_data') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section id="cara-kerja" class="py-20 lg:py-28 bg-gray-100/50 dark:bg-slate-900/30 border-y border-gray-200 dark:border-slate-800 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:flex lg:items-center lg:justify-between gap-16">
                
                <div class="lg:w-1/2 mb-12 lg:mb-0">
                    <h2 class="text-brand-600 dark:text-brand-400 font-bold tracking-wider uppercase text-xs mb-3">{{ __('landing.s.proses_sederhana') }}</h2>
                    <h3 class="text-3xl md:text-4xl font-extrabold mb-6 text-slate-900 dark:text-white">{{ __('landing.s.dari_data_mentah_menjadi_chatbot_dalam_hitungan') }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-lg mb-10">{{ __('landing.s.platform_kami_menangani_alur_kerja_rag_retrieval') }}</p>
                    
                    <div class="space-y-4">
                        <!-- Step 1 Tab -->
                        <div id="step-tab-1" onclick="switchStep(1)" class="cursor-pointer flex gap-4 p-5 rounded-xl border border-brand-500 bg-white dark:bg-slate-900 shadow-md transition-all group">
                            <div id="step-num-1" class="flex-shrink-0 w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm transition-colors">1</div>
                            <div>
                                <h4 class="text-lg font-bold mb-1 text-slate-900 dark:text-white transition-colors">{{ __('landing.s.hubungkan_pengetahuan_anda') }}</h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.unggah_berkas_pdf_docx_atau_berikan_url_website') }}</p>
                            </div>
                        </div>

                        <!-- Step 2 Tab -->
                        <div id="step-tab-2" onclick="switchStep(2)" class="cursor-pointer flex gap-4 p-5 rounded-xl border border-transparent hover:bg-white/50 dark:hover:bg-slate-900/50 transition-all group">
                            <div id="step-num-2" class="flex-shrink-0 w-8 h-8 rounded-full border-2 border-gray-300 dark:border-slate-700 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold text-sm transition-colors group-hover:border-brand-500 group-hover:text-brand-500">2</div>
                            <div>
                                <h4 class="text-lg font-bold mb-1 text-slate-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">{{ __('landing.s.sesuaikan_tampilan_aturan') }}</h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.atur_warna_merek_avatar_bot_salam_pembuka_serta') }}</p>
                            </div>
                        </div>

                        <!-- Step 3 Tab -->
                        <div id="step-tab-3" onclick="switchStep(3)" class="cursor-pointer flex gap-4 p-5 rounded-xl border border-transparent hover:bg-white/50 dark:hover:bg-slate-900/50 transition-all group">
                            <div id="step-num-3" class="flex-shrink-0 w-8 h-8 rounded-full border-2 border-gray-300 dark:border-slate-700 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold text-sm transition-colors group-hover:border-brand-500 group-hover:text-brand-500">3</div>
                            <div>
                                <h4 class="text-lg font-bold mb-1 text-slate-900 dark:text-white group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">{{ __('landing.s.sematkan_dan_bagikan') }}</h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ __('landing.s.dapatkan_kode_widget_obrolan_mengambang_atau_tau') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:w-1/2 relative select-none">
                    <!-- Dashboard Mockup Frame -->
                    <div class="bg-white dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 shadow-xl overflow-hidden flex flex-col h-[450px]">
                        <!-- Mac header -->
                        <div class="bg-gray-100 dark:bg-slate-900 px-4 py-3 flex items-center gap-2 border-b border-gray-200 dark:border-slate-800 relative z-20">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                            <div class="mx-auto text-xs text-gray-500 font-medium font-mono flex items-center gap-1">
                                <i data-lucide="shield-check" class="w-3 h-3"></i> cekat.biz.id/dasbor
                            </div>
                        </div>
                        
                        <!-- Interactive Canvas -->
                        <div class="relative flex-1 bg-white dark:bg-slate-950 overflow-hidden" id="animation-canvas">
                            
                            <!-- Fake Cursor -->
                            <div id="demo-cursor" class="absolute z-[60] text-slate-900 dark:text-white transition-all duration-[600ms] ease-in-out pointer-events-none opacity-0 drop-shadow-md" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                <i data-lucide="mouse-pointer-2" class="w-6 h-6 fill-white dark:fill-slate-900 -rotate-12"></i>
                                <div id="cursor-click-effect" class="absolute top-0 left-0 w-6 h-6 bg-brand-500 rounded-full opacity-0 scale-0 transition-all duration-300 -z-10"></div>
                            </div>

                            <!-- View 1: Upload (Visible by default) -->
                            <div id="view-1" class="absolute inset-0 p-6 flex flex-col transition-opacity duration-300 opacity-100 z-10">
                                <div class="flex justify-between items-center mb-6">
                                    <h5 class="font-bold text-slate-900 dark:text-white flex items-center gap-2"><i data-lucide="database" class="w-4 h-4 text-brand-500"></i> {{ __('landing.s.sumber_data') }}</h5>
                                </div>
                                <div class="relative flex-1 flex items-center justify-center">
                                    <!-- Draggable Mock File -->
                                    <div id="drag-file" class="absolute bg-white dark:bg-slate-800 px-4 py-3 rounded-lg shadow-xl border border-gray-200 dark:border-slate-700 flex items-center gap-3 transition-all duration-[600ms] ease-in-out z-50 opacity-0 scale-90" style="top: -20px; left: -20px;">
                                        <div class="p-2 bg-red-50 dark:bg-red-500/10 text-red-500 rounded-md"><i data-lucide="file-text" class="w-5 h-5"></i></div>
                                        <div>
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ __('landing.s.panduan_produk_pdf') }}</div>
                                            <div class="text-xs text-gray-500">{{ __('landing.s.2_4_mb') }}</div>
                                        </div>
                                    </div>
                                    <!-- Dropzone -->
                                    <div id="dropzone" class="w-full h-full border-2 border-dashed border-gray-300 dark:border-slate-700 rounded-xl flex flex-col items-center justify-center bg-gray-50/50 dark:bg-slate-900/50 transition-colors duration-300">
                                        <div id="dropzone-content" class="text-center transition-opacity duration-300">
                                            <div class="w-12 h-12 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm text-gray-400">
                                                <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                                            </div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ __('landing.s.tarik_lepas_file_anda_ke_sini') }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ __('landing.s.mendukung_pdf_docx_txt') }}</p>
                                        </div>
                                        <!-- Uploading State -->
                                        <div id="upload-state" class="absolute inset-0 flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
                                            <i data-lucide="loader-2" class="w-8 h-8 text-brand-500 animate-spin mb-4"></i>
                                            <p id="upload-text" class="text-sm font-medium text-slate-900 dark:text-white">{{ __('landing.s.mengekstrak_vektor') }}</p>
                                            <div class="w-48 h-2 bg-gray-200 dark:bg-slate-800 rounded-full mt-3 overflow-hidden">
                                                <div id="upload-progress" class="h-full bg-brand-500 w-0 transition-all duration-[2000ms] ease-out"></div>
                                            </div>
                                        </div>
                                        <!-- Success State -->
                                        <div id="success-state" class="absolute inset-0 flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
                                            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-500 rounded-full flex items-center justify-center mb-3">
                                                <i data-lucide="check" class="w-6 h-6"></i>
                                            </div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ __('landing.s.data_berhasil_dilatih') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View 2: Customize (Hidden by default) -->
                            <div id="view-2" class="absolute inset-0 p-6 flex transition-opacity duration-300 opacity-0 pointer-events-none z-10 gap-6">
                                <!-- Settings Panel -->
                                <div class="w-1/2 flex flex-col space-y-5">
                                    <h5 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 mb-2"><i data-lucide="sliders" class="w-4 h-4 text-brand-500"></i> {{ __('landing.s.kustomisasi') }}</h5>
                                    
                                    <div>
                                        <label for="mock-input-name" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">{{ __('landing.s.nama_bot') }}</label>
                                        <div class="relative">
                                            <input type="text" id="mock-input-name" value="Bot Bawaan" class="w-full bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-md py-2 px-3 text-sm text-slate-900 dark:text-white focus:outline-none pointer-events-none" readonly>
                                            <div id="input-cursor-name" class="absolute top-2.5 left-[85px] w-0.5 h-4 bg-brand-500 animate-pulse hidden"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">{{ __('landing.s.warna_tema') }}</p>
                                        <div class="flex gap-2">
                                            <div class="w-8 h-8 rounded-full bg-slate-900 border-2 border-transparent relative"><i data-lucide="check" class="absolute inset-0 m-auto w-4 h-4 text-white"></i></div>
                                            <div id="color-target-purple" class="w-8 h-8 rounded-full bg-purple-600 border-2 border-transparent"></div>
                                            <div class="w-8 h-8 rounded-full bg-blue-600 border-2 border-transparent"></div>
                                            <div class="w-8 h-8 rounded-full bg-rose-600 border-2 border-transparent"></div>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">{{ __('landing.s.pesan_pembuka') }}</p>
                                        <div class="bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-md p-2 text-xs text-gray-600 dark:text-gray-300 h-16 pointer-events-none">
                                            <span id="mock-greeting-text">{{ __('landing.s.hai_ada_yang_bisa_dibantu') }}</span><span id="input-cursor-greeting" class="w-0.5 h-3 bg-brand-500 inline-block align-middle ml-0.5 hidden animate-pulse"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Preview Panel -->
                                <div class="w-1/2 border-l border-gray-200 dark:border-slate-800 pl-6 flex flex-col justify-end">
                                    <div class="border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-md flex-col flex h-[280px]">
                                        <div id="mock-chat-header" class="bg-slate-900 px-3 py-2.5 flex items-center gap-2 transition-colors duration-500">
                                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-white"><i data-lucide="bot" class="w-3 h-3"></i></div>
                                            <span id="mock-chat-title" class="text-white text-xs font-medium">{{ __('landing.s.bot_bawaan') }}</span>
                                        </div>
                                        <div class="flex-1 bg-gray-50 dark:bg-slate-900/50 p-3 flex flex-col gap-2">
                                            <div class="bg-gray-200 dark:bg-slate-800 p-2.5 rounded-lg rounded-tl-none w-[85%]">
                                                <p id="mock-chat-greeting" class="text-[10px] text-gray-700 dark:text-gray-300">{{ __('landing.s.hai_ada_yang_bisa_dibantu') }}</p>
                                            </div>
                                        </div>
                                        <div class="p-2 border-t border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-950">
                                            <div class="h-6 bg-gray-100 dark:bg-slate-900 rounded-md w-full border border-gray-200 dark:border-slate-800"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View 3: Embed (Hidden by default) -->
                            <div id="view-3" class="absolute inset-0 p-6 flex flex-col items-center justify-center transition-opacity duration-300 opacity-0 pointer-events-none z-10">
                                <div class="w-full max-w-sm">
                                    <div class="text-center mb-6">
                                        <div class="w-12 h-12 bg-blue-50 dark:bg-blue-500/10 text-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                            <i data-lucide="code" class="w-6 h-6"></i>
                                        </div>
                                        <h5 class="font-bold text-slate-900 dark:text-white">{{ __('landing.s.tambahkan_ke_website_anda') }}</h5>
                                        <p class="text-xs text-gray-500 mt-1">{{ __('landing.s.salin_kode_ini_dan_tempel_di_dalam_tag_lt_head_g') }}</p>
                                    </div>
                                    
                                    <div class="relative group">
                                        <div class="absolute inset-0 bg-gradient-to-r from-brand-500 to-blue-500 rounded-lg blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
                                        <div class="relative bg-slate-900 rounded-lg border border-slate-700 p-4 font-mono text-[10px] text-emerald-400 overflow-hidden shadow-2xl">
                                            <div class="flex items-center gap-1.5 mb-2 border-b border-slate-700 pb-2">
                                                <div class="w-2 h-2 rounded-full bg-slate-700"></div>
                                                <div class="w-2 h-2 rounded-full bg-slate-700"></div>
                                                <div class="w-2 h-2 rounded-full bg-slate-700"></div>
                                                <span class="text-slate-500 ml-2">{{ __('landing.s.index_html') }}</span>
                                            </div>
                                            <code>&lt;script src="https://cekat.biz.id/widget/widget.js"&gt;&lt;/script&gt;<br>
                                            &lt;script&gt;<br>
                                            &nbsp;&nbsp;window.CSAIConfig = { widgetId: 'bot_xyz987' };<br>
                                            &lt;/script&gt;</code>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex justify-center">
                                        <button id="mock-copy-btn" class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-slate-900 dark:text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 transition-all w-40 justify-center">
                                            <i data-lucide="copy" class="w-4 h-4" id="copy-icon"></i> <span id="copy-text">{{ __('integration.s.salin_kode') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="alur-rag" class="py-16 lg:py-20 relative overflow-hidden bg-white dark:bg-slate-950 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-8 relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 text-brand-700 dark:bg-brand-400/10 dark:text-brand-300 border border-brand-100 dark:border-transparent text-sm font-medium mb-4">
                    <i data-lucide="workflow" class="w-4 h-4"></i> {{ __('landing.s.teknologi_di_balik_cekat') }}
                </div>
                <h2 class="text-3xl md:text-4xl font-extrabold mb-4 text-slate-900 dark:text-white">{{ __('landing.s.bagaimana_alur_rag_bekerja_secara_real_time') }}</h2>
                <p class="text-slate-600 dark:text-slate-300 text-lg">{{ __('landing.s.lihat_bagaimana_cekat_memahami_pertanyaan_pelang') }}</p>
            </div>

            <!-- Flowchart Container dengan React Flow (Seamless & Clean) -->
            <div class="rag-flow-stage relative w-full overflow-hidden">
                <div id="react-flow-root" class="rag-flow-shell w-full overflow-hidden bg-transparent"></div>
            </div>
        </div>
    </section>

    <section id="harga" class="py-20 lg:py-28 bg-gray-50/50 dark:bg-slate-900/30 border-t border-gray-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-brand-600 dark:text-brand-400 font-bold tracking-wider uppercase text-xs mb-3">{{ __('docs.s.harga') }}</h2>
                <h3 class="text-3xl md:text-4xl font-extrabold mb-6 text-slate-900 dark:text-white">{{ __('landing.s.harga_yang_sederhana_dan_transparan') }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-lg">{{ __('landing.s.mulai_secara_gratis_tingkatkan_paket_saat_anda_m') }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                @forelse ($plans as $plan)
                    @php
                        $isPopular = $plan->slug === 'pro';
                        $priceLabel = $plan->price > 0
                            ? 'Rp' . number_format((float) $plan->price, 0, ',', '.')
                            : 'Rp0';
                        $periodLabel = $plan->billing_period === 'yearly' ? '/tahun' : '/bulan';
                        $taglines = [
                            'starter' => 'Sempurna untuk pengujian dan proyek pribadi berskala kecil.',
                            'pro' => 'Untuk bisnis berkembang yang membutuhkan dukungan andal.',
                            'business' => 'Batas kustom dan dukungan khusus untuk skala besar.',
                        ];
                        $tagline = $taglines[$plan->slug] ?? ($plan->description ?: '');
                        $planLimits = app(\App\Services\Billing\PlanLimitService::class);
                        $planWidgets = $planLimits->limit($plan, 'total_channels');
                        $bullets = [
                            $planWidgets . ' ' . \Illuminate\Support\Str::plural('Chatbot', $planWidgets),
                            number_format($planLimits->limit($plan, 'monthly_messages'), 0, ',', '.') . ' Pesan / bulan',
                            $planLimits->limit($plan, 'knowledge_documents') . ' Dokumen & ' . $planLimits->limit($plan, 'faqs') . ' FAQ' . ($planWidgets > 1 ? ' per bot' : ''),
                        ];
                        if ($planWidgets === 1) {
                            $bullets[] = 'Sematkan di 1 website';
                        }
                        $analytics = $planLimits->featureValue($plan, 'analytics');
                        if ($analytics === 'basic') {
                            $bullets[] = 'Analitik Dasar';
                        } elseif ($analytics) {
                            $bullets[] = 'Analitik Lanjutan';
                        }
                        if (! empty($planLimits->featureValue($plan, 'custom_branding'))) {
                            $bullets[] = 'Hapus Merek Cekat';
                        }
                        if (! empty($planLimits->featureValue($plan, 'api_access'))) {
                            $bullets[] = 'Akses API';
                        }
                        if (! empty($planLimits->featureValue($plan, 'priority_support'))) {
                            $bullets[] = 'Dukungan Prioritas';
                        }
                    @endphp
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 {{ $isPopular ? 'border-2 border-brand-500 dark:border-brand-500 relative shadow-lg md:-translate-y-2 hover:shadow-xl transition-all' : 'border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow' }} flex flex-col">
                        @if ($isPopular)
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-brand-500 text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider animate-icon-pulse">
                                {{ __('landing.s.paling_populer') }}
                            </div>
                        @endif
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $plan->name }}</h4>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">{{ $tagline }}</p>
                        <div class="mb-6 flex items-baseline gap-1">
                            <span class="text-4xl font-bold text-slate-900 dark:text-white">{{ $priceLabel }}</span>
                            <span class="text-gray-500 dark:text-gray-400 text-sm font-medium">{{ $periodLabel }}</span>
                        </div>
                        <ul class="space-y-4 mb-8 flex-grow">
                            @foreach ($bullets as $bullet)
                                <li class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                                    <i data-lucide="check" class="w-4 h-4 text-brand-500 mt-0.5"></i> {{ $bullet }}
                                </li>
                            @endforeach
                        </ul>
                        @if ($plan->price <= 0)
                            @auth
                                <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}" class="block w-full text-center py-2.5 rounded-lg border border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors font-medium text-sm">{{ __('landing.s.mulai_gratis') }}</a>
                            @else
                                <a href="/register" class="block w-full text-center py-2.5 rounded-lg border border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors font-medium text-sm">{{ __('landing.s.mulai_gratis') }}</a>
                            @endauth
                        @elseif ($loop->last)
                            <a href="#kontak" class="block w-full text-center py-2.5 rounded-lg border border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors font-medium text-sm">{{ __('landing.s.hubungi_penjualan') }}</a>
                        @else
                            @auth
                                <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}" class="w-full py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white transition-colors font-medium text-sm flex justify-center items-center gap-2 group">
                                    {{ __('landing.s.berlangganan_sekarang') }} <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                                </a>
                            @else
                                <a href="/register" class="w-full py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white transition-colors font-medium text-sm flex justify-center items-center gap-2 group">
                                    {{ __('landing.s.berlangganan_sekarang') }} <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                                </a>
                            @endauth
                        @endif
                    </div>
                @empty
                    <p class="text-gray-600 dark:text-gray-400 md:col-span-3 text-center">{{ __('landing.s.harga_sedang_tidak_tersedia_silakan_hubungi_kami') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <section id="kontak" class="py-24 lg:py-32 relative border-t border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-500/10 dark:bg-brand-500/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="w-16 h-16 bg-brand-100 dark:bg-brand-900/50 text-brand-600 dark:text-brand-400 rounded-2xl flex items-center justify-center mx-auto mb-8 shadow-sm">
                <i data-lucide="rocket" class="w-8 h-8 animate-icon-pulse"></i>
            </div>
            
            <h2 class="text-3xl md:text-5xl font-extrabold mb-6 text-slate-900 dark:text-white tracking-tight">
                {{ __('landing.s.siap_mengotomatisasi_layanan_pelanggan_anda') }}
            </h2>
            
            <p class="text-gray-600 dark:text-gray-400 text-lg md:text-xl mb-10 max-w-2xl mx-auto leading-relaxed">
                {{ __('landing.s.bergabunglah_dengan_ratusan_bisnis_lainnya_buat') }} <span class="font-semibold text-slate-900 dark:text-gray-300">{{ __('landing.s.tanpa_kartu_kredit') }}</span>
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                @auth
                    <a href="{{ auth()->user()->isAdmin() ? '/admin/dashboard' : '/dashboard' }}" class="inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all hover:-translate-y-1 hover:shadow-lg hover:shadow-brand-500/30 group w-full sm:w-auto">
                        {{ __('landing.s.buka_dasbor') }}
                        <i data-lucide="arrow-right" class="w-5 h-5 transition-transform group-hover:translate-x-1"></i>
                    </a>
                @else
                    <a href="/register" class="inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-8 py-4 rounded-xl font-bold text-lg transition-all hover:-translate-y-1 hover:shadow-lg hover:shadow-brand-500/30 group w-full sm:w-auto">
                        {{ __('landing.s.buat_akun_gratis_sekarang') }}
                        <i data-lucide="arrow-right" class="w-5 h-5 transition-transform group-hover:translate-x-1"></i>
                    </a>
                @endauth
            </div>

            <p class="mt-10 text-gray-600 dark:text-gray-400">
                {{ __('landing.s.butuh_bantuan_atau_ingin_bertanya_soal_paket_unt') }}
                <a href="mailto:support@cekat.biz.id" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline">support@cekat.biz.id</a>
                {{ __('landing.s.tim_kami_membalas_pada_jam_kerja') }}
            </p>
        </div>
    </section>

    <footer class="bg-gray-50 dark:bg-slate-950 border-t border-gray-200 dark:border-slate-800 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 mb-12">
                <div class="col-span-2 lg:col-span-2">
                    <div class="flex items-center gap-2 mb-4 group cursor-pointer">
                        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center transition-transform group-hover:scale-105 group-hover:animate-icon-wiggle">
                            <i data-lucide="bot" class="text-white w-5 h-5"></i>
                        </div>
                        <span class="font-bold text-xl tracking-tight text-slate-900 dark:text-white">Cekat<span class="text-brand-600 dark:text-brand-400">.biz.id</span></span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-6 max-w-sm leading-relaxed">
                        {{ __('landing.s.memberdayakan_bisnis_untuk_membuat_asisten_ai_ce') }}
                    </p>
                </div>
                
                <div>
                    <h4 class="text-slate-900 dark:text-white font-semibold mb-4 text-sm uppercase tracking-wider">{{ __('landing.s.produk') }}</h4>
                    <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="#fitur" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">{{ __('docs.s.fitur') }}</a></li>
                        <li><a href="/channels" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">{{ __('general.s.integrasi') }}</a></li>
                        <li><a href="#harga" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">{{ __('docs.s.harga') }}</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-slate-900 dark:text-white font-semibold mb-4 text-sm uppercase tracking-wider">{{ __('landing.s.sumber_daya') }}</h4>
                    <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="/docs/api" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">{{ __('landing.s.dokumentasi') }}</a></li>
                        <li><a href="/docs/api" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">{{ __('landing.s.referensi_api') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-slate-900 dark:text-white font-semibold mb-4 text-sm uppercase tracking-wider">{{ __('landing.s.legal') }}</h4>
                    <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <li><a href="{{ route('legal.privacy') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">{{ __('legal.s.kebijakan_privasi') }}</a></li>
                        <li><a href="{{ route('legal.terms') }}" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">{{ __('landing.s.syarat_ketentuan') }}</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('landing.s.2026_cekat_biz_id_seluruh_hak_cipta_dilindungi') }}</p>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 font-medium">
                    <span>{{ __('admin.s.status') }}</span> <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> {{ __('landing.s.semua_sistem_beroperasi') }}</span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
        updateThemeIcons();

        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 10) {
                nav.classList.add('shadow-md');
            } else {
                nav.classList.remove('shadow-md');
            }
        });

        // Chat Simulation
        const chatContainer = document.getElementById('chat-simulation');
        const conversationSequence = [
            { type: 'user', text: 'Apakah AI ini bisa membaca manual PDF perusahaan kami?' },
            { type: 'bot', text: 'Tentu! Anda bisa mengunggah file PDF, DOCX, TXT, atau menempelkan tautan website. Saya akan memprosesnya secara otomatis dan menjawab pertanyaan pelanggan murni berdasarkan isi dokumen Anda.' },
            { type: 'user', text: 'Bagaimana cara memasangnya di website saya?' },
            { type: 'bot', text: 'Sangat mudah. Anda hanya perlu menyalin satu baris kode JavaScript yang kami sediakan ke dalam tag <head> website Anda. Widget obrolan akan langsung aktif!' }
        ];

        let sequenceIndex = 0;

        function createMessageElement(type, text, isTyping = false) {
            const wrapper = document.createElement('div');
            wrapper.className = `flex gap-3 max-w-[85%] opacity-0 transform translate-y-4 transition-all duration-300 ${type === 'user' ? 'ml-auto flex-row-reverse' : ''}`;
            
            let avatar = '';
            if (type === 'bot') {
                avatar = `
                <div class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-900/50 flex-shrink-0 flex items-center justify-center mt-1 text-brand-600 dark:text-brand-400">
                    <i data-lucide="bot" class="w-4 h-4"></i>
                </div>`;
            }

            const bubbleClass = type === 'user' 
                ? 'bg-slate-900 dark:bg-brand-600 text-white p-3.5 rounded-2xl rounded-tr-none text-sm shadow-sm'
                : 'bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-gray-200 p-3.5 rounded-2xl rounded-tl-none text-sm border border-gray-200 dark:border-slate-700 shadow-sm';

            const content = isTyping 
                ? `<span class="flex gap-1 items-center h-5"><span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span><span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span><span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></span></span>`
                : text;

            wrapper.innerHTML = `
                ${avatar}
                <div class="${bubbleClass}" id="msg-${Date.now()}">
                    ${content}
                </div>
            `;
            return wrapper;
        }

        function runChatSimulation() {
            if (sequenceIndex >= conversationSequence.length) return;
            const currentMsg = conversationSequence[sequenceIndex];
            
            setTimeout(() => {
                if(currentMsg.type === 'user') {
                    const msgEl = createMessageElement('user', currentMsg.text);
                    chatContainer.appendChild(msgEl);
                    lucide.createIcons();
                    setTimeout(() => {
                        msgEl.classList.remove('opacity-0', 'translate-y-4');
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }, 50);
                    sequenceIndex++;
                    runChatSimulation();
                } else {
                    const typingEl = createMessageElement('bot', '', true);
                    chatContainer.appendChild(typingEl);
                    lucide.createIcons();
                    setTimeout(() => {
                        typingEl.classList.remove('opacity-0', 'translate-y-4');
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }, 50);

                    const typingDuration = Math.min(1500 + (currentMsg.text.length * 20), 3000);
                    setTimeout(() => {
                        const bubble = typingEl.querySelector('div[id^="msg-"]');
                        bubble.innerHTML = '';
                        bubble.classList.add('typing-cursor');
                        
                        let charIndex = 0;
                        const typeInterval = setInterval(() => {
                            bubble.innerHTML = currentMsg.text.substring(0, charIndex);
                            chatContainer.scrollTop = chatContainer.scrollHeight;
                            charIndex++;
                            
                            if (charIndex > currentMsg.text.length) {
                                clearInterval(typeInterval);
                                bubble.classList.remove('typing-cursor');
                                sequenceIndex++;
                                runChatSimulation();
                            }
                        }, 25);
                    }, typingDuration);
                }
            }, 1000); 
        }

        window.addEventListener('load', () => {
            setTimeout(runChatSimulation, 1500);
            switchStep(1);
        });

        const sleep = ms => new Promise(r => {
            let t = setTimeout(r, ms);
            activeTimeouts.push(t);
        });

        let activeTimeouts = [];
        let currentStep = 1;

        function clearAllTimeouts() {
            activeTimeouts.forEach(clearTimeout);
            activeTimeouts = [];
        }

        async function switchStep(step) {
            currentStep = step;
            clearAllTimeouts();
            
            for (let i = 1; i <= 3; i++) {
                const tab = document.getElementById(`step-tab-${i}`);
                const num = document.getElementById(`step-num-${i}`);
                const view = document.getElementById(`view-${i}`);
                
                if (i === step) {
                    tab.classList.remove('border-transparent', 'hover:bg-white/50', 'dark:hover:bg-slate-900/50');
                    tab.classList.add('border-brand-500', 'bg-white', 'dark:bg-slate-900', 'shadow-md');
                    num.classList.remove('border-2', 'border-gray-300', 'dark:border-slate-700', 'text-gray-500', 'dark:text-gray-400', 'bg-transparent');
                    num.classList.add('bg-brand-600', 'text-white', 'border-brand-600');
                    view.classList.remove('opacity-0', 'pointer-events-none');
                    view.classList.add('opacity-100');
                } else {
                    tab.classList.add('border-transparent', 'hover:bg-white/50', 'dark:hover:bg-slate-900/50');
                    tab.classList.remove('border-brand-500', 'bg-white', 'dark:bg-slate-900', 'shadow-md');
                    num.classList.add('border-2', 'border-gray-300', 'dark:border-slate-700', 'text-gray-500', 'dark:text-gray-400', 'bg-transparent');
                    num.classList.remove('bg-brand-600', 'text-white', 'border-brand-600');
                    view.classList.add('opacity-0', 'pointer-events-none');
                    view.classList.remove('opacity-100');
                }
            }

            const cursor = document.getElementById('demo-cursor');
            cursor.style.opacity = '0';
            await sleep(300);

            if (step === 1) await runAnimStep1();
            if (step === 2) await runAnimStep2();
            if (step === 3) await runAnimStep3();
        }

        async function triggerClickEffect(cursorEl) {
            const effect = document.getElementById('cursor-click-effect');
            effect.classList.remove('opacity-0', 'scale-0');
            effect.classList.add('opacity-50', 'scale-150');
            await sleep(200);
            effect.classList.remove('opacity-50', 'scale-150');
            effect.classList.add('opacity-0', 'scale-0');
        }

        async function runAnimStep1() {
            const cursor = document.getElementById('demo-cursor');
            const file = document.getElementById('drag-file');
            const dropzoneContent = document.getElementById('dropzone-content');
            const uploadState = document.getElementById('upload-state');
            const uploadProgress = document.getElementById('upload-progress');
            const successState = document.getElementById('success-state');
            const dropzone = document.getElementById('dropzone');

            file.style.transform = 'translate(40px, 30px) scale(0.9)';
            file.style.opacity = '0';
            dropzoneContent.style.opacity = '1';
            uploadState.style.opacity = '0';
            successState.style.opacity = '0';
            uploadProgress.style.width = '0%';
            dropzone.classList.remove('border-brand-500', 'bg-brand-50/50', 'dark:bg-brand-900/20');
            
            await sleep(500);
            cursor.style.opacity = '1';
            cursor.style.transform = 'translate(100px, 150px)';
            file.style.opacity = '1';
            
            await sleep(800);
            cursor.style.transform = 'translate(120px, 45px)';
            await sleep(700);
            
            await triggerClickEffect(cursor);
            file.style.transform = 'translate(40px, 30px) scale(1.05)';
            file.classList.add('shadow-2xl');
            
            await sleep(400);
            cursor.style.transform = 'translate(250px, 200px)';
            file.style.transform = 'translate(150px, 170px) scale(1.05)';
            dropzone.classList.add('border-brand-500', 'bg-brand-50/50', 'dark:bg-brand-900/20');
            
            await sleep(800);
            file.classList.remove('shadow-2xl');
            file.style.transform = 'translate(150px, 170px) scale(0)';
            file.style.opacity = '0';
            
            cursor.style.transform = 'translate(350px, 300px)';
            cursor.style.opacity = '0';

            dropzoneContent.style.opacity = '0';
            await sleep(300);
            uploadState.style.opacity = '1';
            
            await sleep(200);
            uploadProgress.style.width = '100%';
            
            await sleep(2200);
            uploadState.style.opacity = '0';
            await sleep(300);
            successState.style.opacity = '1';
        }

        async function runAnimStep2() {
            const cursor = document.getElementById('demo-cursor');
            const targetColorBtn = document.getElementById('color-target-purple');
            const chatHeader = document.getElementById('mock-chat-header');
            const inputName = document.getElementById('mock-input-name');
            const chatTitle = document.getElementById('mock-chat-title');
            const greetingText = document.getElementById('mock-greeting-text');
            const chatGreeting = document.getElementById('mock-chat-greeting');
            const cursorName = document.getElementById('input-cursor-name');
            
            chatHeader.className = 'bg-slate-900 px-3 py-2.5 flex items-center gap-2 transition-colors duration-500';
            inputName.value = 'Bot Bawaan';
            chatTitle.innerText = 'Bot Bawaan';
            greetingText.innerText = 'Hai! Ada yang bisa dibantu?';
            chatGreeting.innerText = 'Hai! Ada yang bisa dibantu?';
            cursorName.classList.add('hidden');

            await sleep(500);
            cursor.style.opacity = '1';
            cursor.style.transform = 'translate(300px, 300px)';

            await sleep(600);
            cursor.style.transform = 'translate(100px, 150px)';
            await sleep(700);
            await triggerClickEffect(cursor);
            
            targetColorBtn.innerHTML = '<i data-lucide="check" class="absolute inset-0 m-auto w-4 h-4 text-white"></i>';
            targetColorBtn.previousElementSibling.innerHTML = '';
            chatHeader.className = 'bg-purple-600 px-3 py-2.5 flex items-center gap-2 transition-colors duration-500';
            lucide.createIcons();
            
            await sleep(800);
            cursor.style.transform = 'translate(150px, 50px)';
            await sleep(700);
            await triggerClickEffect(cursor);
            
            cursorName.classList.remove('hidden');
            cursor.style.transform = 'translate(200px, 100px)';
            
            await sleep(400);
            const newName = "Asisten Cerdas";
            inputName.value = "";
            chatTitle.innerText = "";
            for (let i = 0; i < newName.length; i++) {
                if (currentStep !== 2) return;
                inputName.value += newName[i];
                chatTitle.innerText += newName[i];
                cursorName.style.left = `${10 + (i * 7)}px`; 
                await sleep(100);
            }
            cursorName.classList.add('hidden');
            await sleep(1000);
            cursor.style.opacity = '0';
        }

        async function runAnimStep3() {
            const cursor = document.getElementById('demo-cursor');
            const copyBtn = document.getElementById('mock-copy-btn');
            const copyIcon = document.getElementById('copy-icon');
            const copyText = document.getElementById('copy-text');

            copyBtn.className = 'bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-slate-900 dark:text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 transition-all w-40 justify-center';
            copyText.innerText = 'Salin Kode';
            copyIcon.setAttribute('data-lucide', 'copy');
            lucide.createIcons();

            await sleep(800);
            cursor.style.opacity = '1';
            cursor.style.transform = 'translate(300px, 50px)';
            
            await sleep(600);
            cursor.style.transform = 'translate(250px, 280px)';
            
            await sleep(700);
            await triggerClickEffect(cursor);
            
            copyBtn.className = 'bg-emerald-500 border border-emerald-500 text-white px-6 py-2.5 rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 transition-all w-40 justify-center';
            copyText.innerText = 'Tersalin!';
            copyIcon.setAttribute('data-lucide', 'check');
            lucide.createIcons();

            await sleep(800);
            cursor.style.transform = 'translate(300px, 400px)';
            cursor.style.opacity = '0';
        }

        // ==========================================
        // RAG INTERACTIVE SIMULATION & REACT FLOW
        // ==========================================
        const h = React.createElement;

        const ragScenarios = {
            whatsapp: {
                id: 'whatsapp',
                title: 'WhatsApp & Knowledge Base',
                badge: 'Fitur Unggulan',
                question: 'Apakah paket Pro bisa dipakai untuk WhatsApp dan data knowledge base internal?',
                tokens: ['Paket Pro', 'WhatsApp API', 'Knowledge Base'],
                vectorCoords: ['+0.381', '-0.912', '+0.047', '+0.814', '-0.259'],
                latency: 14,
                kbDocs: [
                    {
                        title: 'Integrasi WhatsApp Cloud API',
                        snippet: 'Paket Pro mendukung direct koneksi WhatsApp Cloud API & webhook CS otomatis 24/7.',
                        highlight: 'WhatsApp Cloud API & webhook CS',
                        score: '98.4%',
                        isMatch: true
                    },
                    {
                        title: 'Multi-Format Knowledge Base',
                        snippet: 'Unggah PDF/DOCX dokumen internal hingga 20 file per bot dengan isolasi data terenkripsi.',
                        highlight: 'dokumen internal hingga 20 file per bot',
                        score: '96.2%',
                        isMatch: true
                    },
                    {
                        title: 'Ketentuan SLA & Uptime Server',
                        snippet: 'Jaminan uptime 99.9% untuk seluruh paket berbayar di cloud server Cekat.',
                        highlight: '',
                        score: '38.5%',
                        isMatch: false
                    }
                ],
                reasoning: [
                    { title: 'Injeksi Konteks', detail: '2 potongan dokumen relevan disematkan ke prompt AI' },
                    { title: 'Validasi Anti-Halusinasi', detail: '100% konsisten data bisnis (tanpa asumsi liar)' },
                    { title: 'Sintesis Respon', detail: 'Formulasi jawaban ramah & siap dikirim ke user' }
                ],
                output: 'Ya, tentu! Paket Pro mendukung integrasi resmi WhatsApp Business serta sinkronisasi knowledge base dokumen internal perusahaan dengan keamanan data terisolasi.',
                citation: 'Panduan_Integrasi_WhatsApp_v2.pdf (Bab 3)'
            },
            enterprise: {
                id: 'enterprise',
                title: 'Kapasitas Paket Business',
                badge: 'Pricing & Skala',
                question: 'Berapa kuota dokumen dan jumlah chatbot pada paket Business Cekat?',
                tokens: ['Kuota Dokumen', 'Jumlah Chatbot', 'Paket Business'],
                vectorCoords: ['+0.724', '+0.115', '-0.582', '+0.903', '+0.331'],
                latency: 11,
                kbDocs: [
                    {
                        title: 'Kapasitas Dokumen Business',
                        snippet: 'Paket Business menyediakan hingga 100 dokumen & 999 FAQ per bot untuk 10 chatbot aktif.',
                        highlight: '100 dokumen & 999 FAQ per bot',
                        score: '99.1%',
                        isMatch: true
                    },
                    {
                        title: 'Akses API & Dedicated Instance',
                        snippet: 'Akses REST API langsung, kuota token tinggi, dan opsi dedicated database vektor.',
                        highlight: 'Akses REST API langsung',
                        score: '94.8%',
                        isMatch: true
                    },
                    {
                        title: 'Paket Starter & Free Tier',
                        snippet: 'Paket Starter gratis dibatasi 100 pesan & 3 dokumen per bulan untuk uji coba tim berskala kecil.',
                        highlight: '',
                        score: '31.2%',
                        isMatch: false
                    }
                ],
                reasoning: [
                    { title: 'Injeksi Konteks', detail: 'Klausul kuota dokumen paket Business diekstrak' },
                    { title: 'Validasi Anti-Halusinasi', detail: 'Angka 100 dokumen diverifikasi akurat dari tabel harga' },
                    { title: 'Sintesis Respon', detail: 'Menyajikan jawaban transparan dan detail untuk prospek' }
                ],
                output: 'Paket Business menyediakan hingga 100 dokumen & 999 FAQ per bot untuk 10 chatbot, kuota 10.000 pesan/bulan, akses REST API penuh, dan dukungan prioritas.',
                citation: 'Skema_Harga_Business.pdf #Halaman 4'
            }
        };

        const ragSteps = [
            {
                id: 'input',
                title: '1. Input Pengguna',
                desc: 'Pertanyaan pelanggan diketik ke input box',
                edge: null,
                camera: { x: 138, y: 155, zoom: 1.25 },
                mobileCamera: { x: 138, y: 155, zoom: 0.98 }
            },
            {
                id: 'rag',
                title: '2. Vector Pipeline',
                desc: 'Tokenisasi & transformasi teks ke representasi vektor',
                edge: 'input-rag',
                camera: { x: 468, y: 155, zoom: 1.25 },
                mobileCamera: { x: 468, y: 155, zoom: 0.98 }
            },
            {
                id: 'kb',
                title: '3. Knowledge Base',
                desc: 'Pencarian semantik super cepat di database dokumen',
                edge: 'rag-kb',
                camera: { x: 798, y: 155, zoom: 1.2 },
                mobileCamera: { x: 798, y: 155, zoom: 0.94 }
            },
            {
                id: 'model',
                title: '4. AI Reasoning',
                desc: 'Injeksi konteks & validasi agar tidak ada halusinasi',
                edge: 'kb-model',
                camera: { x: 1128, y: 155, zoom: 1.2 },
                mobileCamera: { x: 1128, y: 155, zoom: 0.94 }
            },
            {
                id: 'output',
                title: '5. Output Jawaban',
                desc: 'Jawaban akurat siap dikirimkan ke pelanggan',
                edge: 'model-output',
                camera: { x: 1458, y: 155, zoom: 1.15 },
                mobileCamera: { x: 1458, y: 155, zoom: 0.9 }
            }
        ];

        function RagNode({ data }) {
            const className = [
                'rag-node',
                data.active ? 'is-active' : '',
                data.complete ? 'is-complete' : ''
            ].join(' ');

            const sc = data.scenario;

            return h('div', { className, style: { '--node-accent': data.accent } },
                h(ReactFlow.Handle, {
                    type: 'target',
                    position: ReactFlow.Position.Left,
                    style: { opacity: 0 }
                }),

                // Node Header
                h('div', { className: 'rag-node-header' },
                    h('div', { className: 'rag-node-icon' }, data.icon),
                    h('div', { className: 'flex-1' },
                        h('div', { className: 'rag-node-title' },
                            data.title,
                            data.active && h('span', { className: 'w-2 h-2 rounded-full bg-cyan-400 animate-ping inline-block' })
                        ),
                        h('div', { className: 'rag-node-subtitle' }, data.subtitle)
                    )
                ),

                // 1. INPUT NODE CONTENT
                data.kind === 'input' && h('div', { className: 'space-y-2' },
                    h('div', { className: 'rag-input-box ' + (data.active || data.complete ? 'is-clicked' : '') },
                        h('div', { className: 'text-[11.5px] leading-relaxed text-slate-200' },
                            h('span', null, data.typedText),
                            data.active && !data.sendClicked && h('span', { className: 'rag-caret' })
                        ),
                        h('div', { className: 'flex items-center justify-between mt-2 pt-1 border-t border-slate-700/60' },
                            h('span', { className: 'text-[9.5px] text-slate-400 font-mono flex items-center gap-1' },
                                h('span', { className: 'w-1.5 h-1.5 rounded-full ' + (data.active ? 'bg-cyan-400 animate-pulse' : 'bg-slate-500') }),
                                data.sendClicked ? 'Terkirim ↵' : (data.active ? 'Mengetik...' : 'Siap')
                            ),
                            h('div', {
                                className: 'rag-send-btn ' + (data.sendClicked ? 'is-active' : '') + ' cursor-pointer',
                                title: 'Kirim Pertanyaan'
                            },
                                h('svg', { className: 'w-3.5 h-3.5', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2.2, strokeLinecap: 'round', strokeLinejoin: 'round' },
                                    h('path', { d: 'm22 2-7 20-4-9-9-4Z' }),
                                    h('path', { d: 'M22 2 11 13' })
                                )
                            )
                        )
                    ),
                    // Miniature Virtual Hand Cursor on Input Node
                    data.active && h('div', {
                        className: 'absolute pointer-events-none transition-all duration-700 z-20 ' + (data.cursorClicking ? 'is-clicking' : ''),
                        style: {
                            top: data.cursorPos ? `${data.cursorPos.y}px` : '45px',
                            left: data.cursorPos ? `${data.cursorPos.x}px` : '30px'
                        }
                    },
                        h('svg', { className: 'w-6 h-6 text-cyan-300 drop-shadow-md -rotate-12', viewBox: '0 0 24 24', fill: 'currentColor' },
                            h('path', { d: 'M5.5 3.21V20.8c0 .45.54.67.85.35l4.86-4.86a.5.5 0 0 1 .35-.15h6.87a.5.5 0 0 0 .35-.85L6.35 2.86a.5.5 0 0 0-.85.35Z' })
                        )
                    )
                ),

                // 2. RAG PIPELINE NODE CONTENT
                data.kind === 'rag' && h('div', { className: 'space-y-2 text-xs' },
                    h('div', { className: 'flex items-center justify-between rounded-lg bg-slate-900/90 border border-slate-800 p-2 text-[10.5px]' },
                        h('span', { className: 'text-slate-400 font-medium' }, 'Latency Embedding:'),
                        h('span', { className: 'text-teal-300 font-mono font-bold flex items-center gap-1' },
                            h('span', { className: 'w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse' }),
                            data.active || data.complete ? `${sc.latency} ms` : 'Standby'
                        )
                    ),
                    h('div', { className: 'space-y-1' },
                        h('div', { className: 'text-[9.5px] uppercase tracking-wider text-slate-400 font-semibold' }, 'Tokenisasi Kata Kunci:'),
                        h('div', { className: 'flex flex-wrap gap-1' },
                            sc.tokens.map((tok, i) =>
                                h('span', {
                                    key: tok,
                                    className: 'px-1.5 py-0.5 rounded text-[10px] font-medium transition-all duration-300 ' +
                                        (data.active || data.complete
                                            ? 'bg-teal-500/20 text-teal-300 border border-teal-500/30'
                                            : 'bg-slate-800 text-slate-500 border border-slate-700/50')
                                }, tok)
                            )
                        )
                    ),
                    h('div', { className: 'rounded-lg bg-slate-900/90 border border-slate-800 p-2 font-mono text-[9px] space-y-0.5' },
                        h('div', { className: 'text-slate-400 flex justify-between' },
                            h('span', null, 'Vector (1536-dim Float):'),
                            h('span', { className: 'text-cyan-400' }, 'Cosine Sim')
                        ),
                        h('div', { className: 'text-cyan-300 tracking-tight overflow-hidden text-ellipsis whitespace-nowrap' },
                            data.active || data.complete ? `[${sc.vectorCoords.join(', ')}...]` : '[0.000, 0.000, 0.000...]'
                        )
                    )
                ),

                // 3. KNOWLEDGE BASE NODE CONTENT
                data.kind === 'kb' && h('div', { className: 'rag-kb-list' },
                    data.active && h('div', { className: 'rag-scanner-sweep' }),
                    h('div', { className: 'flex items-center justify-between text-[10px] text-slate-400 font-mono mb-0.5' },
                        h('span', null, 'Semantic Index Scan:'),
                        h('span', { className: 'font-bold ' + (data.active || data.complete ? 'text-teal-300' : 'text-slate-500') },
                            data.active ? `${data.msCounter} ms` : (data.complete ? 'Selesai: 38 ms' : 'Menunggu')
                        )
                    ),
                    sc.kbDocs.map((doc, idx) => {
                        const isMatch = doc.isMatch && (data.active || data.complete);
                        const isDimmed = !doc.isMatch && (data.active || data.complete);
                        return h('div', {
                            key: doc.title,
                            className: 'rag-kb-item ' + (isMatch ? 'is-found' : '') + ' ' + (isDimmed ? 'is-dimmed' : ''),
                            style: { transitionDelay: `${idx * 120}ms` }
                        },
                            h('div', { className: 'flex items-center justify-between gap-1' },
                                h('span', { className: 'font-semibold text-slate-200 truncate flex items-center gap-1.5' },
                                    h('span', { className: 'w-1.5 h-1.5 rounded-full flex-none ' + (isMatch ? 'bg-teal-400' : 'bg-slate-500') }),
                                    doc.title
                                ),
                                h('span', {
                                    className: 'text-[9.5px] px-1.5 py-0.2 rounded font-mono font-bold ' +
                                        (isMatch ? 'bg-teal-500/25 text-teal-300 border border-teal-500/40' : 'text-slate-500')
                                }, doc.score)
                            ),
                            h('div', { className: 'text-[9.5px] leading-tight text-slate-400 line-clamp-2' },
                                isMatch && doc.highlight ?
                                    h(React.Fragment, null,
                                        doc.snippet.split(doc.highlight)[0],
                                        h('span', { className: 'rag-kb-highlight' }, doc.highlight),
                                        doc.snippet.split(doc.highlight)[1]
                                    ) : doc.snippet
                            )
                        );
                    })
                ),

                // 4. AI MODEL REASONING NODE CONTENT
                data.kind === 'model' && h('div', { className: 'space-y-1.5 text-xs' },
                    h('div', { className: 'flex items-center justify-between text-[10px] text-slate-400 font-mono mb-1' },
                        h('span', null, 'Grounding Validation:'),
                        h('span', { className: 'text-purple-300 font-bold' },
                            data.active || data.complete ? 'Zero Hallucination' : 'Standby'
                        )
                    ),
                    sc.reasoning.map((item, idx) => {
                        const isDone = (data.active && data.reasoningStep >= idx) || data.complete;
                        return h('div', {
                            key: item.title,
                            className: 'rag-reasoning-step ' + (isDone ? 'is-done' : '')
                        },
                            h('div', {
                                className: 'w-4 h-4 rounded-full flex items-center justify-center flex-none ' +
                                    (isDone ? 'bg-purple-500/30 text-purple-300' : 'bg-slate-800 text-slate-600')
                            },
                                isDone ? '✓' : '•'
                            ),
                            h('div', { className: 'flex-1 min-w-0' },
                                h('div', { className: 'font-semibold text-[10px] text-slate-200 truncate' }, item.title),
                                h('div', { className: 'text-[9px] text-slate-400 truncate' }, item.detail)
                            )
                        );
                    }),
                    h('div', { className: 'h-1.5 w-full bg-slate-800 rounded-full overflow-hidden mt-1' },
                        h('div', {
                            className: 'h-full bg-gradient-to-r from-purple-500 to-cyan-400 transition-all duration-700 rounded-full',
                            style: {
                                width: data.complete ? '100%' : (data.active ? `${(data.reasoningStep + 1) * 33}%` : '0%')
                            }
                        })
                    )
                ),

                // 5. OUTPUT NODE CONTENT
                data.kind === 'output' && h('div', { className: 'space-y-2' },
                    h('div', { className: 'rag-output-bubble' },
                        h('div', { className: 'text-[11px] leading-relaxed text-emerald-100' },
                            data.active || data.complete ? data.streamedOutput : 'Menunggu sintesis reasoning...'
                        ),
                        (data.active || data.complete) && h('div', { className: 'mt-2 pt-1.5 border-t border-emerald-500/30 flex items-center justify-between text-[9px] text-emerald-300/80 font-mono' },
                            h('span', { className: 'flex items-center gap-1' },
                                h('span', { className: 'w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse' }),
                                'Valid 100% Data Bisnis'
                            ),
                            h('span', null, 'Siap Kirim ↵')
                        )
                    ),
                    (data.active || data.complete) && h('div', {
                        className: 'flex items-center gap-1 text-[9.5px] text-slate-400 px-2 py-1 rounded-md bg-slate-900/80 border border-slate-800 font-mono truncate',
                        title: sc.citation
                    },
                        h('svg', { className: 'w-3 h-3 text-cyan-400 flex-none', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 2 },
                            h('path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' }),
                            h('polyline', { points: '14 2 14 8 20 8' })
                        ),
                        h('span', { className: 'truncate' }, sc.citation)
                    )
                ),

                h(ReactFlow.Handle, {
                    type: 'source',
                    position: ReactFlow.Position.Right,
                    style: { opacity: 0 }
                })
            );
        }
        function RagFlowCanvas() {
            const reactFlowInstance = ReactFlow.useReactFlow();
            const [scenarioKey, setScenarioKey] = React.useState('whatsapp');
            const [stepIndex, setStepIndex] = React.useState(0);
            const [typedText, setTypedText] = React.useState('');
            const [sendClicked, setSendClicked] = React.useState(false);
            const [cursorPos, setCursorPos] = React.useState({ x: 30, y: 35 });
            const [cursorClicking, setCursorClicking] = React.useState(false);
            const [msCounter, setMsCounter] = React.useState(0);
            const [reasoningStep, setReasoningStep] = React.useState(0);
            const [streamedOutput, setStreamedOutput] = React.useState('');
            const [isMobileFlow, setIsMobileFlow] = React.useState(() => window.innerWidth < 768);
            const [isDarkFlow, setIsDarkFlow] = React.useState(() => document.documentElement.classList.contains('dark'));

            const currentScenario = ragScenarios[scenarioKey];
            const activeStep = ragSteps[stepIndex];

            // Mobile resize listener
            React.useEffect(() => {
                function handleResize() {
                    setIsMobileFlow(window.innerWidth < 768);
                }
                window.addEventListener('resize', handleResize);
                return () => window.removeEventListener('resize', handleResize);
            }, []);

            // Keep React Flow colors in sync with the page theme toggle.
            React.useEffect(() => {
                function syncTheme() {
                    setIsDarkFlow(document.documentElement.classList.contains('dark'));
                }

                const observer = new MutationObserver(syncTheme);
                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                syncTheme();
                return () => observer.disconnect();
            }, []);

            // Smooth camera tracking
            React.useEffect(() => {
                const target = isMobileFlow ? activeStep.mobileCamera : activeStep.camera;
                reactFlowInstance.setCenter(target.x, target.y, {
                    zoom: target.zoom,
                    duration: 1100
                });
            }, [activeStep.id, isMobileFlow, reactFlowInstance]);

            // Step 0: Realistic Human Typing & Send Action
            React.useEffect(() => {
                if (stepIndex !== 0) return;

                let isCancelled = false;
                setTypedText('');
                setSendClicked(false);
                setCursorClicking(false);
                setCursorPos({ x: 30, y: 35 });

                const q = currentScenario.question;

                // 1. Cursor smoothly enters input box
                const t1 = setTimeout(() => {
                    if (isCancelled) return;
                    setCursorPos({ x: 80, y: 45 });
                    setCursorClicking(true);

                    // 2. Typing begins with human-like rhythm
                    const t2 = setTimeout(() => {
                        if (isCancelled) return;
                        setCursorClicking(false);
                        let charIdx = 0;

                        const typeTimer = setInterval(() => {
                            if (isCancelled) {
                                clearInterval(typeTimer);
                                return;
                            }
                            charIdx++;
                            setTypedText(q.slice(0, charIdx));

                            // When ~75% typed, cursor starts moving towards Send button
                            if (charIdx > q.length * 0.75) {
                                setCursorPos({ x: 232, y: 88 });
                            }

                            if (charIdx >= q.length) {
                                clearInterval(typeTimer);

                                // 3. Click the send button
                                setTimeout(() => {
                                    if (isCancelled) return;
                                    setCursorPos({ x: 238, y: 88 });
                                    setCursorClicking(true);
                                    setSendClicked(true);

                                    // Advance to step 1 (Vector Pipeline)
                                    setTimeout(() => {
                                        if (isCancelled) return;
                                        setCursorClicking(false);
                                        setStepIndex(1);
                                    }, 850);
                                }, 350);
                            }
                        }, 36);
                    }, 350);
                }, 500);

                return () => {
                    isCancelled = true;
                    clearTimeout(t1);
                };
            }, [stepIndex, scenarioKey]);

            // Step 1: Vector Pipeline
            React.useEffect(() => {
                if (stepIndex !== 1) return;
                setTypedText(currentScenario.question);
                setSendClicked(true);

                const timer = setTimeout(() => {
                    setStepIndex(2);
                }, 2800);
                return () => clearTimeout(timer);
            }, [stepIndex, scenarioKey]);

            // Step 2: Knowledge Base Live Millisecond Counter & Semantic Scan
            React.useEffect(() => {
                if (stepIndex !== 2) return;

                let isCancelled = false;
                setMsCounter(0);
                const startTime = Date.now();
                const targetMs = 38;
                const duration = 1100;

                const msInterval = setInterval(() => {
                    if (isCancelled) {
                        clearInterval(msInterval);
                        return;
                    }
                    const elapsed = Date.now() - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    setMsCounter(Math.floor(progress * targetMs));

                    if (progress >= 1) {
                        clearInterval(msInterval);
                        setTimeout(() => {
                            if (!isCancelled) setStepIndex(3);
                        }, 1800);
                    }
                }, 35);

                return () => {
                    isCancelled = true;
                    clearInterval(msInterval);
                };
            }, [stepIndex]);

            // Step 3: AI Model Reasoning Steps
            React.useEffect(() => {
                if (stepIndex !== 3) return;

                let isCancelled = false;
                setReasoningStep(0);

                const s1 = setTimeout(() => {
                    if (!isCancelled) setReasoningStep(1);
                }, 700);

                const s2 = setTimeout(() => {
                    if (!isCancelled) setReasoningStep(2);
                }, 1500);

                const s3 = setTimeout(() => {
                    if (!isCancelled) setStepIndex(4);
                }, 3000);

                return () => {
                    isCancelled = true;
                    clearTimeout(s1);
                    clearTimeout(s2);
                    clearTimeout(s3);
                };
            }, [stepIndex]);

            // Step 4: Output Streaming Typewriter & Auto-Alternate Scenarios
            React.useEffect(() => {
                if (stepIndex !== 4) return;

                let isCancelled = false;
                setStreamedOutput('');
                const fullText = currentScenario.output;
                let charIdx = 0;

                const streamTimer = setInterval(() => {
                    if (isCancelled) {
                        clearInterval(streamTimer);
                        return;
                    }
                    charIdx += 2;
                    setStreamedOutput(fullText.slice(0, charIdx));

                    if (charIdx >= fullText.length) {
                        clearInterval(streamTimer);
                        // Pause for reading, then alternate to the next scenario!
                        setTimeout(() => {
                            if (!isCancelled) {
                                setScenarioKey(prev => prev === 'whatsapp' ? 'enterprise' : 'whatsapp');
                                setStepIndex(0);
                            }
                        }, 3800);
                    }
                }, 28);

                return () => {
                    isCancelled = true;
                    clearInterval(streamTimer);
                };
            }, [stepIndex, scenarioKey]);

            // Node Definitions with clean horizontal layout
            const baseNodes = [
                { id: 'input', position: { x: 0, y: 50 }, kind: 'input', icon: 'IN', title: '1. Input Pengguna', subtitle: 'Pertanyaan Pelanggan', accent: '#38bdf8' },
                { id: 'rag', position: { x: 330, y: 50 }, kind: 'rag', icon: 'VEC', title: '2. Vector Pipeline', subtitle: 'Embedding 1536-dim', accent: '#2dd4bf' },
                { id: 'kb', position: { x: 660, y: 50 }, kind: 'kb', icon: 'KB', title: '3. Knowledge Base', subtitle: 'Pencarian Semantik', accent: '#10b981' },
                { id: 'model', position: { x: 990, y: 50 }, kind: 'model', icon: 'AI', title: '4. Model Reasoning', subtitle: 'Zero-Hallucination', accent: '#a855f7' },
                { id: 'output', position: { x: 1320, y: 50 }, kind: 'output', icon: 'OUT', title: '5. Output Respon', subtitle: 'Jawaban Terverifikasi', accent: '#14b8a6' }
            ];

            const nodes = baseNodes.map((node, index) => ({
                id: node.id,
                type: 'ragNode',
                position: node.position,
                draggable: false,
                data: {
                    ...node,
                    scenario: currentScenario,
                    typedText,
                    sendClicked,
                    cursorPos,
                    cursorClicking,
                    msCounter,
                    reasoningStep,
                    streamedOutput,
                    active: node.id === activeStep.id,
                    complete: index < stepIndex
                }
            }));

            const edges = [
                { id: 'input-rag', source: 'input', target: 'rag', color: '#38bdf8' },
                { id: 'rag-kb', source: 'rag', target: 'kb', color: '#2dd4bf' },
                { id: 'kb-model', source: 'kb', target: 'model', color: '#10b981' },
                { id: 'model-output', source: 'model', target: 'output', color: '#a855f7' }
            ].map((edge) => {
                const isActive = edge.id === activeStep.edge;
                return {
                    id: edge.id,
                    source: edge.source,
                    target: edge.target,
                    type: 'smoothstep',
                    animated: isActive,
                    className: isActive ? 'rag-edge-active' : '',
                    style: {
                        stroke: isActive ? edge.color : (isDarkFlow ? '#334155' : '#cbd5e1'),
                        color: edge.color,
                        strokeWidth: isActive ? 4 : 2,
                        opacity: isActive ? 1 : (isDarkFlow ? 0.45 : 0.7)
                    }
                };
            });

            // Pure, seamless React Flow canvas with no borders or buttons
            return h('div', { className: 'rag-flow-canvas relative h-full w-full overflow-hidden select-none' },
                h(ReactFlow.ReactFlow, {
                    nodes: nodes,
                    edges: edges,
                    nodeTypes: { ragNode: RagNode },
                    nodesDraggable: false,
                    nodesConnectable: false,
                    elementsSelectable: false,
                    panOnDrag: false,
                    zoomOnScroll: false,
                    zoomOnPinch: false,
                    zoomOnDoubleClick: false,
                    preventScrolling: false,
                    minZoom: 0.65,
                    maxZoom: 1.45,
                    proOptions: { hideAttribution: true }
                },
                    h(ReactFlow.Background, { color: isDarkFlow ? '#1e293b' : '#cbd5e1', gap: 24, size: isDarkFlow ? 1.2 : 1.1 })
                )
            );
        }

        function RagFlowApp() {
            return h(ReactFlow.ReactFlowProvider, null,
                h(RagFlowCanvas)
            );
        }

        const rootEl = document.getElementById('react-flow-root');
        if (rootEl) {
            const root = ReactDOM.createRoot(rootEl);
            root.render(h(RagFlowApp));
        }
    </script>

    @php
        $landingWidget = \App\Models\Widget::where('slug', 'landing-page-default')->first();
        $settings = $landingWidget->settings ?? [];
        $widgetName = $landingWidget->name ?? 'Customer Support';
        $color = $settings['primary_color'] ?? '#0f172a';
        $greeting = $settings['greeting'] ?? 'Halo! 👋 Ada yang bisa saya bantu?';
        $position = $settings['position'] ?? 'bottom-right';
        $subtitle = $settings['subtitle'] ?? 'Online • Reply cepat';
        $placeholder = $settings['placeholder'] ?? 'Ketik pesan...';
        $avatarType = $settings['avatar_type'] ?? 'icon';
        $avatarIcon = $settings['avatar_icon'] ?? 'robot';
        $avatarUrl = $settings['avatar_url'] ?? '';
    @endphp
    <script>
        window.CSAIConfig = {
            widgetId: 'landing-page-default',
            apiUrl: '/api/chat',
            position: '{{ $position }}',
            primaryColor: '{{ $color }}',
            title: '{{ addslashes($widgetName) }}',
            subtitle: '{{ addslashes($subtitle) }}',
            greeting: `{!! addslashes($greeting) !!}`,
            placeholder: '{{ addslashes($placeholder) }}',
            avatarType: '{{ $avatarType }}',
            avatarIcon: '{{ $avatarIcon }}',
            avatarUrl: '{{ $avatarUrl }}',
            showBranding: true
        };
    </script>
    <script src="/widget/widget.js?v={{ time() }}"></script>
</body>
</html>
