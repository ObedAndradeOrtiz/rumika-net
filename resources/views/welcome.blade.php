<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rumika SaaS | Sistema modular para clínicas, spas y más</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900" rel="stylesheet" />

    <style>
        :root {
            --primary: #0f8f7f;
            --primary-dark: #087568;
            --primary-deep: #054d45;
            --primary-soft: #dff6f1;
            --text: #101828;
            --muted: #667085;
            --border: #dbe3ee;
            --white: #ffffff;
            --bg: #f4f7fb;
            --bg-soft: #f8fbfd;
            --shadow-lg: 0 30px 80px rgba(15, 23, 42, 0.12);
            --shadow-md: 0 18px 45px rgba(15, 23, 42, 0.08);
            --shadow-sm: 0 10px 24px rgba(15, 23, 42, 0.06);
            --radius-xl: 34px;
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 14px;
            --container: 1320px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Figtree', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(15, 143, 127, .08), transparent 28%),
                radial-gradient(circle at bottom right, rgba(15, 143, 127, .06), transparent 30%),
                linear-gradient(180deg, #f7fbfb 0%, #f4f7fb 32%, #f8fbfd 100%);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            display: block;
        }

        button {
            font: inherit;
        }

        .page {
            width: min(var(--container), calc(100% - 40px));
            margin: 0 auto;
        }

        .site-header {
            padding: 22px 0 14px;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: white;
            background: linear-gradient(135deg, var(--primary), #11a290);
            box-shadow: 0 16px 36px rgba(15, 143, 127, .22);
            flex: 0 0 auto;
        }

        .brand-title {
            display: block;
            margin: 0;
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.05em;
            color: #0f172a;
        }

        .brand-subtitle {
            display: block;
            margin-top: 6px;
            font-size: 13px;
            color: var(--muted);
            font-weight: 700;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            min-height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            font-weight: 900;
            font-size: 15px;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 16px 32px rgba(15, 143, 127, .22);
        }

        .btn-primary:hover {
            box-shadow: 0 18px 36px rgba(15, 143, 127, .28);
        }

        .btn-outline {
            color: var(--primary-dark);
            background: rgba(255, 255, 255, 0.82);
            border-color: var(--border);
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            background: #fff;
            box-shadow: var(--shadow-sm);
        }

        .hero {
            padding: 18px 0 56px;
        }

        .hero-shell {
            position: relative;
            overflow: hidden;
            border-radius: 38px;
            padding: 34px;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .18), transparent 25%),
                linear-gradient(135deg, #0e8072 0%, #0c6f64 45%, #0a5c54 100%);
            box-shadow: 0 30px 80px rgba(7, 74, 68, .26);
            color: white;
        }

        .hero-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 20% 10%, rgba(255, 255, 255, .10), transparent 20%),
                radial-gradient(circle at 80% 25%, rgba(255, 255, 255, .08), transparent 20%);
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr);
            gap: 34px;
            align-items: center;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 11px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .18);
            font-size: 14px;
            font-weight: 800;
            color: rgba(255, 255, 255, .95);
            backdrop-filter: blur(10px);
        }

        .hero-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #91f0dc;
            box-shadow: 0 0 0 6px rgba(145, 240, 220, .14);
        }

        .hero-title {
            margin: 22px 0 0;
            font-size: clamp(42px, 5.4vw, 78px);
            line-height: .96;
            font-weight: 900;
            letter-spacing: -0.08em;
            max-width: 760px;
        }

        .hero-text {
            margin: 24px 0 0;
            max-width: 760px;
            font-size: clamp(17px, 2vw, 21px);
            line-height: 1.72;
            color: rgba(255, 255, 255, .84);
        }

        .hero-actions {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .btn-light {
            background: #fff;
            color: var(--primary-dark);
            box-shadow: 0 16px 32px rgba(7, 74, 68, .18);
        }

        .btn-light:hover {
            box-shadow: 0 20px 36px rgba(7, 74, 68, .24);
        }

        .btn-ghost {
            background: rgba(255, 255, 255, .10);
            border-color: rgba(255, 255, 255, .18);
            color: white;
            backdrop-filter: blur(10px);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, .16);
        }

        .hero-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 28px;
        }

        .hero-tag {
            padding: 11px 15px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .16);
            font-size: 14px;
            font-weight: 700;
            color: rgba(255, 255, 255, .93);
        }

        .hero-side {
            position: relative;
        }

        .preview-card {
            border-radius: 30px;
            background: rgba(255, 255, 255, .96);
            color: var(--text);
            padding: 26px;
            box-shadow: 0 28px 60px rgba(8, 25, 39, .16);
        }

        .preview-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .preview-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .preview-mark {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), #0fa18e);
            display: grid;
            place-items: center;
            color: white;
            flex: 0 0 auto;
        }

        .preview-brand strong {
            display: block;
            font-size: 18px;
            letter-spacing: -0.03em;
        }

        .preview-brand span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .status-pill {
            padding: 9px 13px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-deep);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .02em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .stat-box {
            border: 1px solid #e8edf3;
            background: #fbfdff;
            border-radius: 20px;
            padding: 18px;
            min-height: 108px;
        }

        .stat-box span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-box strong {
            display: block;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.05em;
            color: #0f172a;
        }

        .stat-box small {
            display: block;
            margin-top: 8px;
            color: var(--muted);
            line-height: 1.5;
        }

        .feature-list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .feature-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 15px 16px;
            border-radius: 18px;
            background: #f7fbfb;
            border: 1px solid #e6eef2;
        }

        .feature-row-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .feature-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: var(--primary);
            flex: 0 0 auto;
        }

        .feature-row-left strong {
            font-size: 15px;
            font-weight: 800;
        }

        .feature-state {
            font-size: 12px;
            font-weight: 900;
            color: var(--primary-deep);
            background: #eaf8f4;
            padding: 8px 11px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .hero-note {
            position: absolute;
            left: -12px;
            bottom: 28px;
            background: #ffffff;
            color: var(--text);
            padding: 16px 18px;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(8, 25, 39, .14);
            border: 1px solid #edf2f7;
            min-width: 210px;
        }

        .hero-note span {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 6px;
            font-weight: 700;
        }

        .hero-note strong {
            display: block;
            font-size: 20px;
            letter-spacing: -0.04em;
        }

        .section {
            padding: 74px 0;
        }

        .section-head {
            max-width: 780px;
            margin-bottom: 32px;
        }

        .section-kicker {
            display: inline-block;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: .03em;
            color: var(--primary-dark);
            margin-bottom: 10px;
        }

        .section-title {
            margin: 0;
            font-size: clamp(32px, 4vw, 52px);
            line-height: 1.04;
            letter-spacing: -0.065em;
            color: #0f172a;
        }

        .section-text {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.72;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .info-card {
            position: relative;
            overflow: hidden;
            padding: 26px;
            border-radius: 26px;
            background: rgba(255, 255, 255, .88);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 48px rgba(15, 23, 42, .10);
        }

        .info-card::after {
            content: "";
            position: absolute;
            top: -42px;
            right: -42px;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(15, 143, 127, .12), transparent 68%);
        }

        .card-index {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: var(--primary-soft);
            color: var(--primary-deep);
            font-weight: 900;
            margin-bottom: 18px;
            position: relative;
            z-index: 1;
        }

        .info-card h3 {
            margin: 0;
            font-size: 22px;
            letter-spacing: -0.035em;
            color: #111827;
            position: relative;
            z-index: 1;
        }

        .info-card p {
            margin: 12px 0 0;
            color: var(--muted);
            line-height: 1.68;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

        .modules-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr);
            gap: 20px;
            align-items: stretch;
        }

        .modules-panel {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            border-radius: 32px;
            padding: 26px;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .module-card {
            border-radius: 24px;
            padding: 22px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fcfd 100%);
            border: 1px solid #e7eef4;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .module-card:hover {
            transform: translateY(-3px);
            border-color: #cfe5de;
            box-shadow: var(--shadow-sm);
        }

        .module-icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--primary-soft), #edf8f5);
            color: var(--primary-deep);
            margin-bottom: 16px;
        }

        .module-card h3 {
            margin: 0;
            font-size: 20px;
            letter-spacing: -0.035em;
        }

        .module-card p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.62;
            font-size: 15px;
        }

        .module-card ul {
            margin: 14px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .module-card li {
            position: relative;
            padding-left: 18px;
            color: #475467;
            font-size: 14px;
            line-height: 1.5;
        }

        .module-card li::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--primary);
            position: absolute;
            left: 0;
            top: 8px;
        }

        .modules-side {
            background: linear-gradient(160deg, #0f172a 0%, #162032 100%);
            color: white;
            border-radius: 32px;
            padding: 28px;
            box-shadow: 0 26px 56px rgba(15, 23, 42, .18);
            position: relative;
            overflow: hidden;
        }

        .modules-side::before {
            content: "";
            position: absolute;
            inset: auto -60px -60px auto;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(15, 143, 127, .28), transparent 68%);
        }

        .modules-side h3 {
            margin: 0;
            font-size: 28px;
            line-height: 1.08;
            letter-spacing: -0.05em;
        }

        .modules-side p {
            margin: 14px 0 0;
            color: rgba(255, 255, 255, .78);
            line-height: 1.7;
            font-size: 16px;
        }

        .modules-points {
            display: grid;
            gap: 12px;
            margin-top: 22px;
        }

        .modules-point {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .10);
            font-weight: 700;
            color: rgba(255, 255, 255, .94);
        }

        .about-box {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(220px, .55fr);
            gap: 24px;
            align-items: center;
            padding: 34px;
            border-radius: 32px;
            background: linear-gradient(135deg, #ffffff 0%, #f7fbfd 100%);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
        }

        .about-box h3 {
            margin: 0;
            font-size: clamp(28px, 4vw, 44px);
            line-height: 1.06;
            letter-spacing: -0.055em;
        }

        .about-box p {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.72;
        }

        .digitbol-box {
            padding: 22px;
            border-radius: 24px;
            background: #f9fbff;
            border: 1px solid #e6ecf4;
            display: flex;
            flex-direction: column;
            gap: 14px;
            align-items: flex-start;
        }

        .digitbol-logo {
            width: 110px;
            max-width: 100%;
            object-fit: contain;
        }

        .digitbol-box span {
            font-size: 13px;
            color: var(--muted);
            font-weight: 700;
        }

        .digitbol-box strong {
            font-size: 18px;
            line-height: 1.3;
            color: #0f172a;
        }

        .faq-wrap {
            display: grid;
            gap: 14px;
        }

        .faq-item {
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .9);
            border-radius: 22px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .faq-question {
            width: 100%;
            border: 0;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 22px 24px;
            text-align: left;
            cursor: pointer;
        }

        .faq-question:hover {
            background: rgba(15, 143, 127, .02);
        }

        .faq-question span:first-child {
            font-size: 18px;
            font-weight: 800;
            color: #101828;
            line-height: 1.35;
        }

        .faq-icon {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            border: 1px solid #d9e6e3;
            display: grid;
            place-items: center;
            color: var(--primary-dark);
            font-size: 22px;
            font-weight: 500;
            flex: 0 0 auto;
            transition: transform .18s ease, background .18s ease;
            background: #f8fffd;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height .28s ease;
        }

        .faq-answer-inner {
            padding: 0 24px 22px;
            color: var(--muted);
            line-height: 1.72;
            font-size: 16px;
        }

        .faq-item.active .faq-icon {
            transform: rotate(45deg);
            background: var(--primary-soft);
        }

        .cta {
            padding: 26px 0 88px;
        }

        .cta-box {
            border-radius: 34px;
            background: linear-gradient(135deg, #ffffff 0%, #f7fbfd 100%);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            padding: 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .cta-box h3 {
            margin: 0;
            font-size: clamp(30px, 4vw, 44px);
            line-height: 1.05;
            letter-spacing: -0.055em;
        }

        .cta-box p {
            margin: 14px 0 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.68;
            max-width: 720px;
        }

        .cta-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .footer {
            padding: 0 0 34px;
        }

        .footer-box {
            border-top: 1px solid rgba(219, 227, 238, .9);
            padding-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .footer-box strong {
            color: var(--primary-dark);
        }

        .whatsapp-float {
            position: fixed;
            right: 18px;
            bottom: 18px;
            width: 58px;
            height: 58px;
            border-radius: 999px;
            background: #25D366;
            color: white;
            display: grid;
            place-items: center;
            box-shadow: 0 18px 38px rgba(37, 211, 102, .28);
            z-index: 50;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .whatsapp-float:hover {
            transform: translateY(-3px);
            box-shadow: 0 22px 42px rgba(37, 211, 102, .34);
        }

        .reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity .55s ease, transform .55s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 1180px) {
            .hero-grid,
            .modules-wrap,
            .about-box,
            .cta-box {
                grid-template-columns: 1fr;
            }

            .hero-note {
                left: auto;
                right: 16px;
                bottom: 18px;
            }

            .grid-3 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cta-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 860px) {
            .page {
                width: min(100% - 28px, var(--container));
            }

            .site-header {
                padding-top: 16px;
                padding-bottom: 10px;
            }

            .nav {
                align-items: center;
                flex-direction: row;
            }

            .nav-actions {
                display: none;
            }

            .brand-mark {
                width: 48px;
                height: 48px;
                border-radius: 15px;
            }

            .brand-title {
                font-size: 23px;
            }

            .brand-subtitle {
                display: none;
            }

            .hero {
                padding-top: 10px;
                padding-bottom: 42px;
            }

            .hero-shell {
                padding: 26px 18px 24px;
                border-radius: 26px;
            }

            .hero-grid {
                gap: 24px;
            }

            .hero-eyebrow {
                width: 100%;
                justify-content: center;
                font-size: 12px;
                padding: 10px 12px;
                text-align: center;
            }

            .hero-title {
                font-size: 34px;
                line-height: 1.08;
                letter-spacing: -0.055em;
            }

            .hero-text {
                font-size: 15.5px;
                line-height: 1.65;
                margin-top: 18px;
            }

            .hero-actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 10px;
                margin-top: 24px;
            }

            .hero-actions .btn,
            .cta-actions .btn {
                width: 100%;
                min-height: 50px;
                font-size: 14px;
            }

            .hero-tags {
                gap: 8px;
                margin-top: 22px;
            }

            .hero-tag {
                padding: 9px 12px;
                font-size: 12.5px;
            }

            .hero-side {
                display: none;
            }

            .stats-grid,
            .grid-3,
            .modules-grid {
                grid-template-columns: 1fr;
            }

            .section {
                padding: 56px 0;
            }

            .section-text {
                font-size: 16px;
            }

            .modules-panel,
            .modules-side,
            .about-box,
            .cta-box {
                padding: 22px;
                border-radius: 24px;
            }

            .faq-question {
                padding: 18px 18px;
            }

            .faq-answer-inner {
                padding: 0 18px 18px;
                font-size: 15px;
            }

            .footer-box {
                flex-direction: column;
                align-items: flex-start;
            }

            .whatsapp-float {
                width: 54px;
                height: 54px;
                right: 14px;
                bottom: 14px;
            }
        }

        @media (max-width: 480px) {
            .page {
                width: min(100% - 24px, var(--container));
            }

            .hero-title {
                font-size: 31px;
                line-height: 1.1;
            }

            .hero-text {
                font-size: 15px;
            }

            .section-title {
                font-size: 30px;
            }

            .info-card,
            .module-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <header class="site-header">
            <div class="nav">
                <a href="{{ url('/') }}" class="brand">
                    <span class="brand-mark">
                        <x-application-logo class="h-7 w-7 text-white" />
                    </span>

                    <span>
                        <span class="brand-title">Rumika SaaS</span>
                        <span class="brand-subtitle">Sistema base modular para negocios de atención</span>
                    </span>
                </a>

                <div class="nav-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            Entrar al sistema
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline">
                            Iniciar sesión
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary">
                                Registrar empresa
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="hero reveal">
                <div class="hero-shell">
                    <div class="hero-grid">
                        <div>
                            <div class="hero-eyebrow">
                                <span class="hero-dot"></span>
                                Plataforma escalable para múltiples sucursales
                            </div>

                            <h1 class="hero-title">
                                La base modular para organizar tu negocio.
                            </h1>

                            <p class="hero-text">
                                Rumika es un sistema base modular para clínicas, spas, centros de belleza, barberías,
                                dentistas y otros negocios que necesitan gestionar agendas, clientes, historial,
                                inventario, pagos, caja y sucursales de una forma clara, rápida y profesional.
                            </p>

                            <div class="hero-actions">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="btn btn-light">
                                        Entrar al sistema
                                    </a>
                                @else
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="btn btn-light">
                                            Registrar empresa
                                        </a>
                                    @endif

                                    <a href="{{ route('login') }}" class="btn btn-ghost">
                                        Iniciar sesión
                                    </a>
                                @endauth
                            </div>

                            <div class="hero-tags">
                                <span class="hero-tag">Agenda</span>
                                <span class="hero-tag">Clientes</span>
                                <span class="hero-tag">Historial</span>
                                <span class="hero-tag">Inventario</span>
                                <span class="hero-tag">Sucursales</span>
                                <span class="hero-tag">Caja y reportes</span>
                            </div>
                        </div>

                        <div class="hero-side">
                            <div class="preview-card">
                                <div class="preview-top">
                                    <div class="preview-brand">
                                        <div class="preview-mark">
                                            <x-application-logo class="h-6 w-6 text-white" />
                                        </div>

                                        <div>
                                            <strong>Panel Rumika</strong>
                                            <span>Resumen general del negocio</span>
                                        </div>
                                    </div>

                                    <span class="status-pill">MODULAR</span>
                                </div>

                                <div class="stats-grid">
                                    <div class="stat-box">
                                        <span>Citas programadas</span>
                                        <strong>18</strong>
                                        <small>Control del día por sucursal, horario y estado.</small>
                                    </div>

                                    <div class="stat-box">
                                        <span>Clientes activos</span>
                                        <strong>324</strong>
                                        <small>Historial completo de atención y seguimiento.</small>
                                    </div>
                                </div>

                                <div class="feature-list">
                                    <div class="feature-row">
                                        <div class="feature-row-left">
                                            <span class="feature-dot"></span>
                                            <strong>Agenda por sucursal</strong>
                                        </div>
                                        <span class="feature-state">Activo</span>
                                    </div>

                                    <div class="feature-row">
                                        <div class="feature-row-left">
                                            <span class="feature-dot"></span>
                                            <strong>Inventario y productos</strong>
                                        </div>
                                        <span class="feature-state">Activable</span>
                                    </div>

                                    <div class="feature-row">
                                        <div class="feature-row-left">
                                            <span class="feature-dot"></span>
                                            <strong>Pagos, caja y reportes</strong>
                                        </div>
                                        <span class="feature-state">Activable</span>
                                    </div>
                                </div>
                            </div>

                            <div class="hero-note">
                                <span>Diseñado para que cada sucursal</span>
                                <strong>active solo lo que necesita</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section reveal">
                <div class="section-head">
                    <span class="section-kicker">QUÉ ES RUMIKA</span>
                    <h2 class="section-title">
                        Una base flexible para negocios que trabajan con clientes, atención y control operativo.
                    </h2>
                    <p class="section-text">
                        Rumika no es una plantilla rígida. Está pensado como un sistema base modular para crecer según
                        el tipo de negocio y permitir que cada empresa o sucursal habilite sus propias funciones.
                    </p>
                </div>

                <div class="grid-3">
                    <article class="info-card">
                        <div class="card-index">01</div>
                        <h3>Adaptable a varios rubros</h3>
                        <p>
                            Funciona para clínicas, spas, centros de belleza, barberías, dentistas y más negocios que
                            dependen de agenda, seguimiento e historial.
                        </p>
                    </article>

                    <article class="info-card">
                        <div class="card-index">02</div>
                        <h3>Organización por sucursal</h3>
                        <p>
                            Cada sucursal puede tener su propia operación, usuarios, módulos, productos, caja e
                            información sin perder el orden general.
                        </p>
                    </article>

                    <article class="info-card">
                        <div class="card-index">03</div>
                        <h3>Listo para crecer</h3>
                        <p>
                            Puedes comenzar con lo esencial y luego incorporar más procesos sin cambiar de herramienta
                            ni romper tu flujo de trabajo.
                        </p>
                    </article>
                </div>
            </section>

            <section class="section reveal">
                <div class="section-head">
                    <span class="section-kicker">MÓDULOS PRINCIPALES</span>
                    <h2 class="section-title">
                        Funciones claras, ordenadas y pensadas para el trabajo real del día a día.
                    </h2>
                    <p class="section-text">
                        Cada sucursal puede activar únicamente las herramientas que necesita para operar con orden.
                    </p>
                </div>

                <div class="modules-wrap">
                    <div class="modules-panel">
                        <div class="modules-grid">
                            <article class="module-card">
                                <div class="module-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="2"/>
                                        <path d="M8 3V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M16 3V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M3 10H21" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                                <h3>Agenda y citas</h3>
                                <p>Gestiona la programación diaria con mejor control visual y operativo.</p>
                                <ul>
                                    <li>Registro de citas por fecha y hora</li>
                                    <li>Estados de atención y seguimiento</li>
                                    <li>Organización por sucursal y profesional</li>
                                </ul>
                            </article>

                            <article class="module-card">
                                <div class="module-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M16 19C16 16.7909 14.2091 15 12 15C9.79086 15 8 16.7909 8 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                        <path d="M4 19C4 17.3431 5.34315 16 7 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M20 19C20 17.3431 18.6569 16 17 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <h3>Clientes e historial</h3>
                                <p>Ten toda la información del cliente en un solo lugar, sin duplicidad.</p>
                                <ul>
                                    <li>Datos de contacto y registro</li>
                                    <li>Historial de atención y observaciones</li>
                                    <li>Seguimiento de pagos y visitas</li>
                                </ul>
                            </article>

                            <article class="module-card">
                                <div class="module-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 7.5L12 4L20 7.5L12 11L4 7.5Z" stroke="currentColor" stroke-width="2"/>
                                        <path d="M4 12.5L12 16L20 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M4 17.5L12 21L20 17.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <h3>Inventario</h3>
                                <p>Controla productos, movimientos y disponibilidad en cada sede.</p>
                                <ul>
                                    <li>Stock por sucursal</li>
                                    <li>Ingresos y salidas de productos</li>
                                    <li>Control más ordenado del abastecimiento</li>
                                </ul>
                            </article>

                            <article class="module-card">
                                <div class="module-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="2"/>
                                        <path d="M3 10H21" stroke="currentColor" stroke-width="2"/>
                                        <path d="M8 15H10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <h3>Pagos, caja y reportes</h3>
                                <p>Una vista simple para controlar ingresos, movimientos y decisiones.</p>
                                <ul>
                                    <li>Registro de pagos por método</li>
                                    <li>Caja diaria y cierres</li>
                                    <li>Reportes para control operativo</li>
                                </ul>
                            </article>
                        </div>
                    </div>

                    <aside class="modules-side">
                        <h3>Un sistema base que se adapta a tu negocio.</h3>
                        <p>
                            Rumika está pensado para crecer contigo. No necesitas usar todo desde el primer día: cada
                            sucursal puede activar sus módulos según su forma de trabajo.
                        </p>

                        <div class="modules-points">
                            <div class="modules-point">Configuración modular por tipo de negocio</div>
                            <div class="modules-point">Separación operativa por sucursal</div>
                            <div class="modules-point">Escalable para nuevos procesos y áreas</div>
                            <div class="modules-point">Ideal para negocios que quieren orden real</div>
                        </div>
                    </aside>
                </div>
            </section>

            <section class="section reveal">
                <div class="section-head">
                    <span class="section-kicker">QUIÉNES SOMOS</span>
                    <h2 class="section-title">
                        Rumika es una solución creada para facilitar la operación y mejorar el control de cada sede.
                    </h2>
                </div>

                <div class="about-box">
                    <div>
                        <h3>Creado por DigitBol como una base sólida, moderna y lista para crecer.</h3>
                        <p>
                            Rumika nace como una plataforma pensada para negocios que necesitan trabajar con más orden,
                            claridad y estructura. La idea es evitar procesos dispersos y ofrecer una herramienta
                            limpia, práctica y adaptable.
                        </p>
                        <p>
                            Más que una simple interfaz, Rumika busca ser una base profesional para administrar la
                            operación de distintos rubros de atención.
                        </p>
                    </div>

                    <div class="digitbol-box">
                        <img src="{{ asset('digitbol.png') }}" alt="DigitBol" class="digitbol-logo">
                        <span>Un sistema más de</span>
                        <strong>DigitBol</strong>
                        <span>Desarrollo de soluciones y sistemas a medida.</span>
                    </div>
                </div>
            </section>

            <section class="section reveal">
                <div class="section-head">
                    <span class="section-kicker">PREGUNTAS FRECUENTES</span>
                    <h2 class="section-title">
                        Lo más importante, explicado de forma rápida.
                    </h2>
                </div>

                <div class="faq-wrap">
                    <div class="faq-item">
                        <button class="faq-question" type="button">
                            <span>¿Rumika sirve solo para spas?</span>
                            <span class="faq-icon">+</span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                No. Rumika está pensado para clínicas, spas, centros de belleza, barberías,
                                dentistas y otros negocios que necesitan organizar agendas, clientes, inventario,
                                historial y sucursales.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button">
                            <span>¿Cada sucursal puede manejar sus propios módulos?</span>
                            <span class="faq-icon">+</span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                Sí. Una de las ventajas de Rumika es que cada sucursal puede activar solo los módulos
                                que necesita, según el tipo de negocio o su nivel de operación.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button">
                            <span>¿Puedo comenzar con pocas funciones e ir creciendo?</span>
                            <span class="faq-icon">+</span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                Sí. Rumika está planteado como una base escalable. Puedes iniciar con agenda y clientes,
                                y después sumar inventario, caja, reportes u otras áreas cuando lo necesites.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button">
                            <span>¿Es útil para negocios con varias sedes?</span>
                            <span class="faq-icon">+</span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                Sí. Rumika fue pensado para trabajar por sucursales y facilitar la organización de cada
                                una sin perder el control general de la empresa.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" type="button">
                            <span>¿Cómo puedo pedir información o comunicarme?</span>
                            <span class="faq-icon">+</span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                Puedes comunicarte directamente por WhatsApp mediante el botón flotante para recibir más
                                información o conversar con un representante.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cta reveal">
                <div class="cta-box">
                    <div>
                        <h3>Empieza con una base más ordenada para tu negocio.</h3>
                        <p>
                            Registra tu empresa, organiza tus sucursales y activa solo los módulos que realmente
                            necesitas para operar con mayor claridad y control.
                        </p>
                    </div>

                    <div class="cta-actions">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                Entrar al sistema
                            </a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary">
                                    Registrar empresa
                                </a>
                            @endif

                            <a href="{{ route('login') }}" class="btn btn-outline">
                                Iniciar sesión
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-box">
                <div>© {{ date('Y') }} Rumika SaaS. Todos los derechos reservados.</div>
                <div>Creado por <strong>DigitBol</strong></div>
            </div>
        </footer>
    </div>

    <a
        href="https://wa.me/59177348087?text=Hola%2C%20quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20Rumika."
        target="_blank"
        rel="noopener"
        class="whatsapp-float"
        aria-label="Contactar por WhatsApp"
    >
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
            <path fill="currentColor"
                d="M16.02 4C9.4 4 4.02 9.28 4.02 15.78c0 2.08.56 4.1 1.62 5.88L4 28l6.52-1.68a12.2 12.2 0 0 0 5.5 1.32C22.64 27.64 28 22.36 28 15.86C28.02 9.36 22.64 4 16.02 4Zm0 21.62c-1.78 0-3.52-.46-5.04-1.34l-.36-.22l-3.86 1l1.02-3.68l-.24-.38a9.66 9.66 0 0 1-1.5-5.18c0-5.38 4.48-9.76 9.98-9.76S26 10.44 26 15.82c0 5.4-4.48 9.8-9.98 9.8Zm5.48-7.32c-.3-.14-1.78-.86-2.06-.96c-.28-.1-.48-.14-.68.14c-.2.3-.78.96-.96 1.16c-.18.2-.36.22-.66.08c-.3-.14-1.26-.46-2.4-1.46c-.88-.78-1.48-1.74-1.66-2.04c-.18-.3-.02-.46.14-.6c.14-.14.3-.36.46-.54c.16-.18.2-.3.3-.5c.1-.2.06-.38-.02-.54c-.08-.14-.68-1.62-.94-2.22c-.24-.58-.5-.5-.68-.52h-.58c-.2 0-.52.08-.8.38c-.28.3-1.04 1-1.04 2.44s1.06 2.84 1.2 3.04c.14.2 2.08 3.12 5.04 4.38c.7.3 1.26.48 1.68.62c.7.22 1.34.18 1.84.12c.56-.08 1.78-.72 2.04-1.42c.26-.7.26-1.3.18-1.42c-.08-.14-.28-.22-.58-.36Z"/>
        </svg>
    </a>

    <script>
        document.querySelectorAll('.faq-item').forEach((item) => {
            const button = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');

            button.addEventListener('click', () => {
                const isActive = item.classList.contains('active');

                document.querySelectorAll('.faq-item').forEach((otherItem) => {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.faq-answer').style.maxHeight = null;
                });

                if (!isActive) {
                    item.classList.add('active');
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                }
            });
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.12
        });

        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
    </script>
</body>

</html>
