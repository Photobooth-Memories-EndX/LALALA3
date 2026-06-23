<style>

/* =======================================================
   GLOBAL
======================================================= */

.page-body{
    background:#f5f7fb;
    min-height:100vh;
}

.card{
    border:none;
}

a{
    text-decoration:none;
}


/* =======================================================
   STATS
======================================================= */

.stat-card{
    background:white;
    border-radius:22px;
    padding:30px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,.08);
    transition:.3s;
    height:100%;
}

.stat-card:hover{
    transform:translateY(-5px);
}

.stat-card i{
    font-size:34px;
    color:#2563eb;
}

.stat-card h3{
    margin-top:10px;
    margin-bottom:5px;
    font-weight:700;
}

.stat-card p{
    color:#6b7280;
    margin:0;
}

/* =======================================================
   COURSE CARD
======================================================= */

.modern-card{
    border:none;
    border-radius:22px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,.08);
}

.modern-card .card-header{
    padding:24px 28px 0;
}

.modern-card .card-header h2{
    font-size:28px;
    font-weight:700;
    color:#111827;
    margin:0;
}

.course-card{
    background:white;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    transition:.3s;
    height:100%;
}

.course-card:hover{
    transform:translateY(-5px);
}

.course-card img{
    width:100%;
    height:180px;
    object-fit:cover;
}

.course-body{
    padding:16px;
}

.course-body h5{
    min-height:auto;
    font-size:16px;
    font-weight:600;
    line-height:1.5;
    margin-bottom:10px;
}

.course-body h5 a{
    color:#111827;
}

.course-body .badge{
    font-size:13px;
    padding:8px 12px;
}

.course-body a{
    color:#111827;
}

.course-body a:hover{
    color:#2563eb;
}

/* =======================================================
   NEWS (REVISI MASALAH 4: Font Diperbesar & Dipertebal)
======================================================= */

.news-item{
    padding:12px 0;
    border-bottom:1px solid #edf2f7;
}

.news-item:last-child{
    border-bottom:none;
}

.news-link{
    font-size:14px;
    font-weight:500;
    line-height:1.6;
}

.news-link:hover{
    color:#2563eb;
}

/* =======================================================
   OLD CAROUSEL SUPPORT
======================================================= */

@media (max-width: 767px){

    .carousel-inner .carousel-item > div{
        display:none;
    }

    .carousel-inner .carousel-item > div:first-child{
        display:block;
    }

    .modern-hero{
        padding:30px;
    }

    .hero-title{
        font-size:32px;
    }

    .hero-circle{
        width:120px;
        height:120px;
        margin-top:20px;
    }

    .hero-circle h2{
        font-size:30px;
    }
}
.continue-cover{
    width:100%;
    height:220px;
    object-fit:cover;
    border-radius:18px;
}

.continue-title{
    font-size:24px;
    font-weight:700;
}

.continue-content{
    padding-left:10px;
}

.deadline-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 20px;
    border-radius:18px;
    background:#fff;
    border:1px solid #edf2f7;
    margin-bottom:14px;
    transition:.25s;
    text-decoration:none;
    color:inherit;
}

.deadline-row:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,.06);
}

.deadline-left{
    display:flex;
    align-items:center;
    gap:16px;
}

.deadline-icon{
    width:48px;
    height:48px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
}

.deadline-icon.danger{
    background:#fff1f2;
    color:#ef4444;
}

.deadline-icon.warning{
    background:#fff7ed;
    color:#f59e0b;
}

.deadline-icon.primary{
    background:#eff6ff;
    color:#2563eb;
}

.deadline-title{
    font-size:15px;
    font-weight:600;
    color:#111827;
}

.deadline-subtitle{
    font-size:13px;
    color:#6b7280;
    margin-top:4px;
}

.deadline-arrow{
    color:#94a3b8;
}

.empty-state{
    text-align:center;
    padding:30px;
    color:#94a3b8;
}

.empty-state i{
    font-size:42px;
    margin-bottom:10px;
}

.path-item{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:16px;
    font-weight:500;
}

.path-dot{
    width:14px;
    height:14px;
    border-radius:50%;
    border:2px solid #2563eb;
}

.path-item.completed .path-dot{
    background:#22c55e;
    border-color:#22c55e;
}

.path-item.active .path-dot{
    background:#2563eb;
}


.hero-achievement{
    display:flex;
    gap:15px;
    margin-top:25px;
}

.streak-badge{
    background:#fff3cd;
    padding:10px 18px;
    border-radius:12px;
}

.learning-card{
    display:block;
    background:#fff;
    border-radius:20px;
    overflow:hidden;
    text-decoration:none;
    color:inherit;
    transition:.3s;
    box-shadow:0 4px 20px rgba(0,0,0,.05);
    height:100%;
}

.learning-card:hover{
    transform:translateY(-4px);
    color:inherit;
}

.learning-image-wrapper{
    position:relative;
}

.learning-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

.play-button{
    position:absolute;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    width:60px;
    height:60px;
    border-radius:50%;
    background:rgba(255,255,255,.95);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    color:#2563eb;
}

.learning-content{
    padding:20px;
}

.learning-category{
    display:inline-block;
    background:#eef2ff;
    color:#2563eb;
    font-size:12px;
    font-weight:600;
    padding:6px 12px;
    border-radius:999px;
    margin-bottom:12px;
}

.learning-title{
    font-size:18px;
    font-weight:700;
    color:#111827;
    margin-bottom:12px;
    line-height:1.5;
}

.learning-meta{
    font-size:14px;
    color:#64748b;
}

.dashboard-stat{
    background:#fff;
    border-radius:20px;
    padding:25px;
    min-height:140px;
}

.stat-blue{
    border-left:5px solid #2463ff;
}

.stat-green{
    border-left:5px solid #16c172;
}

.stat-red{
    border-left:5px solid #ff4d4f;
}

.continue-image{
    width:100%;
    height:280px;
    object-fit:cover;
    border-radius:20px;
}

.module-filter{
    background:#eef2ff;
    padding:4px;
    border-radius:999px;
    display:flex;
    gap:4px;
}

.module-filter span{
    padding:6px 14px;
    border-radius:999px;
    font-size:12px;
    cursor:pointer;
    color:#64748b;
}

.module-filter span.active{
    background:#fff;
    color:#2563eb;
    font-weight:600;
}

.module-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 0;
    border-bottom:1px solid #edf2f7;
    text-decoration:none;
    color:inherit;
}

.module-row:last-child{
    border-bottom:none;
}

.module-left{
    display:flex;
    align-items:center;
    gap:16px;
}

.module-image{
    width:72px;
    height:72px;
    border-radius:14px;
    object-fit:cover;
    flex-shrink:0;
}

.module-title{
    font-size:15px;
    font-weight:600;
    color:#111827;
    margin-bottom:4px;
}

.module-subtitle{
    font-size:13px;
    color:#6b7280;
}

.module-arrow{
    color:#94a3b8;
    font-size:18px;
}

.module-row:hover{
    color:inherit;
}

.module-row:hover .module-arrow{
    transform:translateX(3px);
    transition:.2s;
}

.news-modern-card,
.gallery-modern-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
}

.news-image,
.gallery-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

.deadline-modern{
    display:flex;
    align-items:center;
    gap:15px;
    padding:15px;
    border-radius:14px;
    background:#fff;
    border:1px solid #edf2f7;
    margin-bottom:12px;
    transition:.3s;
}

.deadline-modern:hover{
    transform:translateY(-2px);
}

.deadline-icon{
    width:45px;
    height:45px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff5f5;
    color:#ef4444;
}

.progress-summary{
    display:flex;
    align-items:center;
    gap:20px;
}

.progress-circle-small{
    width:80px;
    height:80px;
    border-radius:50%;
    background:#eef2ff;
    color:#2563eb;
    font-weight:700;
    font-size:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
}

.progress-modern{
    height:12px;
    border-radius:999px;
    background:#e5e7eb;
}

.progress-modern .progress-bar{
    background:#2563eb;
    border-radius:999px;
}

.news-card-modern{
    display:block;
    background:#fff;
    border-radius:20px;
    overflow:hidden;
    text-decoration:none;
    color:inherit;
    transition:.3s;
    height:100%;
    box-shadow:0 4px 20px rgba(0,0,0,.05);
}

.news-card-modern:hover{
    transform:translateY(-4px);
    color:inherit;
}

.news-modern-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

.news-modern-body{
    padding:20px;
}

.news-category{
    display:inline-block;
    padding:6px 12px;
    border-radius:999px;
    background:#eef2ff;
    color:#2563eb;
    font-size:12px;
    font-weight:600;
    margin-bottom:12px;
}

.news-modern-title{
    font-size:17px;
    font-weight:700;
    line-height:1.5;
    color:#111827;
    min-height:75px;
}

.news-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:15px;
    color:#64748b;
    font-size:13px;
}

.gallery-card-modern{
    display:block;
    background:#fff;
    border-radius:20px;
    overflow:hidden;
    text-decoration:none;
    color:inherit;
    transition:.3s;
    height:100%;
    box-shadow:0 4px 20px rgba(0,0,0,.05);
}

.gallery-card-modern:hover{
    transform:translateY(-4px);
    color:inherit;
}

.gallery-modern-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

.gallery-modern-body{
    padding:20px;
}

.gallery-category{
    display:inline-block;
    padding:6px 12px;
    border-radius:999px;
    background:#ecfdf5;
    color:#16a34a;
    font-size:12px;
    font-weight:600;
    margin-bottom:12px;
}

.gallery-modern-title{
    font-size:17px;
    font-weight:700;
    line-height:1.5;
    color:#111827;
    min-height:60px;
}

.gallery-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:15px;
    color:#64748b;
    font-size:13px;
}

.hero-achievement{
display:flex;
gap:12px;
margin-top:24px;
}

.streak-badge,
.learner-badge{
padding:10px 16px;
border-radius:12px;
font-size:14px;
font-weight:600;
}

.streak-badge{
background:#fff7ed;
color:#ea580c;
}

.learner-badge{
background:#ecfdf5;
color:#16a34a;
}

.hero-mini-stats{
display:flex;
gap:40px;
margin-top:30px;
}

.mini-stat strong{
display:block;
font-size:24px;
font-weight:700;
color:#111827;
}

.mini-stat span{
font-size:13px;
color:#64748b;
}

.hero-progress-wrapper{
position:relative;
text-align:center;
}

.hero-progress-text{
margin-top:-20px;
}

.hero-progress-text h3{
margin-bottom:0;
font-size:28px;
font-weight:700;
}

.hero-progress-text span{
color:#64748b;
font-size:14px;
}

.module-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:18px 0;
    border-bottom:1px solid #eef2f7;
    text-decoration:none;
    color:#111827;
    transition:.2s;
}

.module-row:hover{
    color:#2563eb;
    transform:translateX(4px);
}

.module-left{
    display:flex;
    align-items:center;
    gap:15px;
}

.module-image{
    width:60px;
    height:60px;
    border-radius:12px;
    object-fit:cover;
}

.module-row h5{
    margin-bottom:4px;
    font-size:15px;
    font-weight:600;
}

.module-row small{
    color:#64748b;
}

/* ==================================================
   HERO NEW
================================================== */

.dashboard-hero{
    background:linear-gradient(
        135deg,
        #163250 0%,
        #1e3a8a 100%
    );
    border-radius:28px;
    padding:40px;
    margin-bottom:24px;
    position:relative;
    overflow:hidden;
}

.dashboard-hero::before{
    content:'';
    position:absolute;
    width:320px;
    height:320px;
    border-radius:50%;
    background:rgba(255,255,255,.05);
    top:-120px;
    right:-120px;
}

.hero-label{
    display:inline-block;
    background:rgba(255,255,255,.12);
    color:#fff;
    padding:8px 16px;
    border-radius:999px;
    font-size:13px;
    font-weight:600;
}

.hero-title{
    font-size:42px;
    font-weight:700;
    color:#fff;
    line-height:1.1;
    margin-bottom:0;
}

.hero-name{
    margin-top:-5px;
}

.hero-subtitle{
    margin-top:10px;
    font-size:18px;
    color:rgba(255,255,255,.85);
}

.hero-achievement{
    display:flex;
    gap:12px;
    margin-top:25px;
}

.streak-badge{
    background:rgba(255,193,7,.15);
    color:#4d3600;
    padding:10px 16px;
    border-radius:12px;
}

.learner-badge{
    background:rgba(34,197,94,.15);
    color:#052e14;
    padding:10px 16px;
    border-radius:12px;
}

.hero-progress-text h3{
    color:#fff;
    font-size:48px;
    font-weight:700;
}

.hero-progress-text span{
    color:rgba(255,255,255,.8);
}

@media(max-width:768px){

    .dashboard-hero{
        padding:24px;
    }

    .hero-title{
        font-size:30px;
    }

    .hero-subtitle{
        font-size:15px;
    }

    .hero-achievement{
        flex-wrap:wrap;
    }

}

/* =======================================================
   DARK MODE
======================================================= */
[data-bs-theme="dark"] .deadline-item:hover{
    background:#334155;
}

[data-bs-theme="dark"] .page-body{
    background:#0f172a;
}

[data-bs-theme="dark"] .modern-card,
[data-bs-theme="dark"] .modern-widget,
[data-bs-theme="dark"] .modern-profile-card,
[data-bs-theme="dark"] .stat-card,
[data-bs-theme="dark"] .course-card{
    background:#1e293b;
    color:#f8fafc;
    box-shadow:none;
    border:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .course-body a,
[data-bs-theme="dark"] .news-link,
[data-bs-theme="dark"] h1,
[data-bs-theme="dark"] h2,
[data-bs-theme="dark"] h3,
[data-bs-theme="dark"] h4,
[data-bs-theme="dark"] h5,
[data-bs-theme="dark"] h6{
    color:#f8fafc;
}

[data-bs-theme="dark"] .text-muted,
[data-bs-theme="dark"] .stat-card p{
    color:#94a3b8 !important;
}

[data-bs-theme="dark"] .progress{
    background:#334155;
}

[data-bs-theme="dark"] .progress-bar{
    background:#3b82f6;
}

[data-bs-theme="dark"] .news-item{
    border-bottom:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .hero-badge{
    background:#1e293b;
    color:#60a5fa;
}

[data-bs-theme="dark"] .modern-avatar{
    background:linear-gradient(
        135deg,
        #3b82f6,
        #2563eb
    );
}

[data-bs-theme="dark"] .gallery-cover{
    border:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .course-card:hover,
[data-bs-theme="dark"] .stat-card:hover{
    transform:translateY(-4px);
    border-color:#3b82f6;
}


.navbar .form-control,
header .form-control,
.search-input{
    width:420px !important;
    font-size:15px !important;
    border-radius:12px !important;
}

.navbar .form-control::placeholder{
    font-size:15px;
}

.carousel-inner .carousel-item.active,
.carousel-inner .carousel-item-next,
.carousel-inner .carousel-item-prev{
    display:flex;
}

@media (min-width:768px){

    .carousel-inner .carousel-item-end.active,
    .carousel-inner .carousel-item-next{
        transform:translateX(25%);
    }

    .carousel-inner .carousel-item-start.active,
    .carousel-inner .carousel-item-prev{
        transform:translateX(-25%);
    }

    .c-home .carousel-control-prev,
    .c-home .carousel-control-next{
        width:5% !important;
    }
}

.carousel-inner .carousel-item-end,
.carousel-inner .carousel-item-start{
    transform:translateX(0);
}

.modern-widget:hover,
.modern-profile-card:hover,
.stat-card:hover,
.course-card:hover{
    transform:translateY(-5px);
    box-shadow:0 20px 50px rgba(0,0,0,.12);
}

.navbar .form-control,
.search-input{
    width:380px !important;
    height:44px !important;
    font-size:14px !important;
    border-radius:12px !important;
}

.navbar .avatar{
    width:40px !important;
    height:40px !important;
}

.navbar .nav-link{
    font-size:15px !important;
    font-weight:600;
}

.col-lg-4 .modern-widget{
    margin-bottom:18px;
}

.col-lg-4 .modern-widget h5{
    font-size:18px;
}

.col-lg-4 .news-item{
    padding:8px 0;
}

.col-lg-4 .gallery-title{
    margin-top:8px;
}

.page-body{
    background:#f3f5f9;
}

.container-fluid{
    max-width:1400px;
}

/* HERO */


.hero-achievement{
    display:flex;
    gap:12px;
    margin-top:20px;
}

.streak-badge,
.learner-badge{
    padding:10px 16px;
    border-radius:12px;
    font-size:14px;
    font-weight:600;
}

.streak-badge{
    background:#fff3cd;
}

.learner-badge{
    background:#e8f7ee;
}

/* STATS */

.dashboard-stat{
    background:#fff;
    border-radius:22px;
    padding:24px 28px;
    min-height:140px;
    position:relative;
    transition:.25s;
    box-shadow:0 4px 20px rgba(0,0,0,.04);
}

.dashboard-stat:hover{
    transform:translateY(-3px);
}

.stat-blue{
    border-left:4px solid #2563eb;
}

.stat-green{
    border-left:4px solid #22c55e;
}

.stat-red{
    border-left:4px solid #ef4444;
}

.stat-icon{
    font-size:18px;
    margin-bottom:14px;
}

.stat-blue .stat-icon{
    color:#2563eb;
}

.stat-green .stat-icon{
    color:#22c55e;
}

.stat-red .stat-icon{
    color:#ef4444;
}

.stat-number{
    font-size:42px;
    font-weight:700;
    color:#111827;
    line-height:1;
    margin-bottom:12px;
}

.stat-label{
    font-size:15px;
    color:#64748b;
}

.stat-blue{
    border-left:4px solid #2563eb;
}

.stat-green{
    border-left:4px solid #22c55e;
}

.stat-red{
    border-left:4px solid #ef4444;
}

/* CARD */

.modern-card{
    background:#fff;
    border-radius:24px;
    padding:0;
    margin-top:24px;
    box-shadow:0 4px 20px rgba(0,0,0,.05);
}

.modern-card .card-header{
    padding:24px;
}

.modern-card .card-body{
    padding:24px;
}

/* CONTINUE */

.continue-image{
    width:100%;
    height:280px;
    object-fit:cover;
    border-radius:20px;
}

/* TRAINING MODULE */

.module-item{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 0;
    border-bottom:1px solid #eef2f7;
}

.module-item:last-child{
    border-bottom:none;
}

.module-image{
    width:64px;
    height:64px;
    border-radius:12px;
}

/* NEWS */

.news-modern-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,.05);
}

.news-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

/* GALLERY */

.gallery-modern-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,.05);
}

.gallery-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

/* ==========================================
   DARK MODE FIX
========================================== */

[data-bs-theme="dark"] .dashboard-hero{
    background:linear-gradient(
        135deg,
        #163250 0%,
        #1e3a8a 100%
    );
}

[data-bs-theme="dark"] .modern-card{
    background:#13253f;
    border:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .card-header{
    border-bottom:1px solid rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .section-title,
[data-bs-theme="dark"] .modern-card h2,
[data-bs-theme="dark"] .modern-card h3,
[data-bs-theme="dark"] .modern-card h4,
[data-bs-theme="dark"] .modern-card h5{
    color:#fff !important;
}

[data-bs-theme="dark"] .modern-card p,
[data-bs-theme="dark"] .modern-card small,
[data-bs-theme="dark"] .modern-card span{
    color:#cbd5e1;
}

[data-bs-theme="dark"] .module-row{
    border-color:rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .deadline-modern{
    background:#1b3254;
    border-color:rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .news-modern-card,
[data-bs-theme="dark"] .gallery-modern-card{
    background:#1b3254;
}

[data-bs-theme="dark"] .news-modern-card h5,
[data-bs-theme="dark"] .gallery-modern-card h5{
    color:#ffffff !important;
}

[data-bs-theme="dark"] .news-modern-card p,
[data-bs-theme="dark"] .gallery-modern-card p{
    color:#cbd5e1 !important;
}

[data-bs-theme="dark"] .news-modern-card small,
[data-bs-theme="dark"] .gallery-modern-card small{
    color:#94a3b8 !important;
}
</style>

