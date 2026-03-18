<!-- =========================
     Reward Modal Template (Redesigned + FIX pagination click + FILTER)
     ✅ ULTIMATE UI: สวย/อ่านง่าย/ใช้งานไว
     ✅ เพิ่ม "ดูเฉพาะแนะนำ" + badge แนะนำ + sort แนะนำก่อนเสมอ
     ✅ ไม่ตัดของเดิม (คง filter/search/pagination/redeem เดิมทั้งหมด)
     ✅ NEW: เพิ่ม tab "ประวัติการแลก" ใน modal
========================= -->
<script type="text/x-template" id="reward-modal-template">
    <div class="modal modal-custom fade"
         id="rewardModal"
         ref="rewardModal"
         data-bs-backdrop="static"
         data-bs-keyboard="false"
         tabindex="-1"
         aria-labelledby="rewardLabel"
         aria-hidden="true"
         data-bs-focus="false">

        {{-- ✅ FIX: เอา modal-dialog-scrollable ออก เพื่อไม่ให้มี scroll ซ้อน --}}
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark-2 reward-modal">

                <div class="modal-header">
                    <h5 class="modal-title text-center mb-0 text-dark lh-1"
                        id="rewardLabel"
                        v-text="'พ้อยแลกรางวัล' || 'แลกรางวัล'">
                    </h5>
                    <button type="button" class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    <div class="reward-shell">

                        <!-- ===== Sticky Header Bar ===== -->
                        <div class="reward-sticky top">
                            <!-- Topbar -->
                            <div class="d-flex justify-content-between align-items-center px-3 py-2">
                                <div class="text-light d-flex align-items-center gap-2">
                                    <span class="text-muted">พ้อยท์ของคุณ:</span>
                                    <span class="fw-bolder text-warning" v-text="diamond"></span>

                                    <!-- ✅ สรุปย่อสถานะ list (แสดงเฉพาะแท็บรางวัล) -->
                                    <span class="reward-pill ms-2" v-if="activeTab === 'rewards' && !loading">
                                        <i class="fa fa-list-ul me-1"></i>
                                        @{{ displayCount }}
                                        <span class="text-muted">รายการ</span>
                                    </span>

                                    <span class="reward-pill ms-2" v-if="activeTab === 'rewards' && !loading && featuredCount > 0">
                                        <i class="fa fa-star me-1"></i>
                                        <span class="text-warning fw-bold">@{{ featuredCount }}</span>
                                        <span class="text-muted">แนะนำ</span>
                                    </span>

                                    <!-- ✅ สรุปย่อแท็บประวัติ -->
                                    <span class="reward-pill ms-2" v-if="activeTab === 'history' && !loadingHistory">
                                        <i class="fa fa-history me-1"></i>
                                        @{{ historyDisplayCount }}
                                        <span class="text-muted">รายการ</span>
                                    </span>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-outline-light btn-sm"
                                            :disabled="loading || loadingHistory"
                                            @click.prevent="refreshActiveTab()">
                                        <span v-if="loading || loadingHistory" class="spinner-border spinner-border-sm me-1"></span>
                                        รีเฟรช
                                    </button>
                                </div>
                            </div>

                            <!-- ✅ NEW: Tab Switcher -->
                            <div class="px-3 pb-2">
                                <div class="reward-tabs" role="tablist" aria-label="Reward tabs">
                                    <button type="button"
                                            class="reward-tab-btn"
                                            :class="{ active: activeTab === 'rewards' }"
                                            @click.prevent="setTab('rewards')">
                                        <i class="fa fa-gift me-1"></i>
                                        แลกรางวัล
                                    </button>

                                    <button type="button"
                                            class="reward-tab-btn"
                                            :class="{ active: activeTab === 'history' }"
                                            @click.prevent="setTab('history')">
                                        <i class="fa fa-history me-1"></i>
                                        ประวัติการแลก
                                        <span class="reward-tab-count" v-if="historyMeta.total > 0">@{{ Number(historyMeta.total || 0).toLocaleString() }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- ✅ Filter bar (แสดงเฉพาะแท็บรางวัล) -->
                            <div class="px-3 pb-2" v-if="activeTab === 'rewards'">
                                <!-- Row 1: Search -->
                                <div class="row g-2 align-items-center">
                                    <div class="col-12 col-md-8">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-dark text-light border-0" style="opacity:.9">
                                                <i class="fa fa-search"></i>
                                            </span>
                                            <input type="text"
                                                   class="form-control bg-dark text-light border-0"
                                                   placeholder="ค้นหา: ชื่อ/รายละเอียด/โค้ด..."
                                                   v-model.trim="filters.q"
                                                   @input="onFilterChanged"
                                            >
                                            <button type="button"
                                                    class="btn btn-outline-light"
                                                    @click.prevent="clearFilter"
                                                    :disabled="!filters.q && !filters.reward_type && !filters.featured_only">
                                                ล้าง
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <select class="form-select form-select-sm bg-dark text-light border-0"
                                                v-model="filters.reward_type"
                                                @change="onFilterChanged">
                                            <option value="">ทั้งหมด</option>
                                            <option value="wallet_credit">เครดิต</option>
                                            <option value="wallet_gem">เพชร</option>
                                            <option value="external">ของรางวัล</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Row 2: Beautiful Toggle Pills -->
                                <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                                    <div class="reward-seg" role="tablist" aria-label="Reward quick filters">
                                        <button type="button"
                                                class="reward-seg-btn"
                                                :class="{ active: !filters.featured_only }"
                                                @click.prevent="setFeaturedOnly(false)">
                                            <i class="fa fa-layer-group me-1"></i>
                                            ทั้งหมด
                                        </button>

                                        <button type="button"
                                                class="reward-seg-btn"
                                                :class="{ active: filters.featured_only }"
                                                @click.prevent="setFeaturedOnly(true)">
                                            <i class="fa fa-star me-1"></i>
                                            แนะนำ
                                            <span class="reward-seg-count" v-if="featuredCount > 0">@{{ featuredCount }}</span>
                                        </button>
                                    </div>

                                    <div class="reward-subhint text-muted small text-end">
                                        แสดงครั้งละ @{{ perPage }} รายการ
                                    </div>
                                </div>

                                <!-- Row 3: Active filter chips -->
                                <div class="reward-chips mt-2" v-if="hasAnyFilter">
                                    <span class="chip" v-if="filters.featured_only">
                                        <i class="fa fa-star me-1"></i> เฉพาะแนะนำ
                                        <button type="button" class="chip-x" @click.prevent="setFeaturedOnly(false)" aria-label="Remove featured filter">×</button>
                                    </span>

                                    <span class="chip" v-if="filters.reward_type">
                                        <i class="fa fa-tag me-1"></i> @{{ labelTypeChip(filters.reward_type) }}
                                        <button type="button" class="chip-x" @click.prevent="filters.reward_type=''; onFilterChanged()" aria-label="Remove type filter">×</button>
                                    </span>

                                    <span class="chip" v-if="filters.q">
                                        <i class="fa fa-search me-1"></i> “@{{ filters.q }}”
                                        <button type="button" class="chip-x" @click.prevent="filters.q=''; onFilterChanged()" aria-label="Remove search">×</button>
                                    </span>
                                </div>
                            </div>

                            <hr class="m-0 opacity-25">
                        </div>

                        <!-- ===== Scroll Area ===== -->
                        <div class="reward-scroll px-3 py-3">

                            <!-- =========================
                                 TAB: REWARDS
                            ========================== -->
                            <template v-if="activeTab === 'rewards'">

                                <!-- LOADING -->
                                <div class="text-center text-light py-4" v-if="loading">
                                    <div class="spinner-border spinner-border-sm text-light"></div>
                                    <div class="mt-2">กำลังโหลดรางวัล…</div>
                                </div>

                                <!-- EMPTY -->
                                <div class="text-center text-light py-5" v-else-if="!displayRewards.length">
                                    <i class="fa fa-gift text-info fs-2 mb-2"></i>
                                    <p class="mb-0">ไม่พบรางวัลที่ตรงเงื่อนไข</p>
                                    <p class="text-muted small mb-0">ลองปรับตัวกรอง หรือกด “ล้าง”</p>
                                </div>

                                <!-- LIST -->
                                <div v-else class="reward-grid">
                                    <div class="col-12" v-for="r in displayRewards" :key="r.id">

                                        <div class="reward-card-inner">

                                            <div class="reward-thumb" v-if="imageSrc(r)">
                                                <img :src="imageSrc(r)" alt="reward" loading="lazy">
                                            </div>
                                            <div class="reward-thumb placeholder" v-else>
                                                <i class="fa fa-gift"></i>
                                            </div>

                                            <div class="reward-content">
                                                <div class="reward-toprow">
                                                    <div class="reward-title" v-text="r.name"></div>

                                                    <div class="reward-cost">
                                                        ใช้ <span class="fw-bold">@{{ Number(r.point_cost || 0).toLocaleString() }}</span> พ้อยท์
                                                    </div>
                                                </div>

                                                <div class="reward-desc" v-if="r.description" v-text="r.description"></div>

                                                <div class="reward-benefit">
                                                    <template v-if="r.reward_type === 'wallet_credit'">
                                                        ได้รับ <span class="text-success fw-bold">@{{ formatMoney(r.credit_amount) }} เครดิต</span>
                                                    </template>
                                                    <template v-else-if="r.reward_type === 'wallet_gem'">
                                                        ได้รับ <span class="text-info fw-bold">@{{ formatInt(r.gem_amount) }} เพชร</span>
                                                    </template>
                                                    <template v-else>
                                                        <span class="text-light">ของรางวัลภายนอก</span>
                                                        <span class="text-muted small">(ทีมงานจะติดต่อกลับ)</span>
                                                    </template>
                                                </div>

                                                <div class="reward-badges">
                                                    <!-- ✅ แนะนำ -->
                                                    <span class="badge badge-featured" v-if="Number(r.is_featured) === 1">
                                                        <i class="fa fa-star me-1"></i> แนะนำ
                                                    </span>

                                                    <!-- ✅ limit badge -->
                                                    <span class="badge bg-warning text-dark" v-if="limitBadge(r)" v-text="limitBadge(r)"></span>

                                                    <!-- mode badge -->
                                                    <span class="badge bg-secondary" v-text="labelMode(r)"></span>

                                                    <!-- stock badge -->
                                                    <span class="badge bg-dark border text-muted" v-if="stockText(r)" v-text="stockText(r)"></span>
                                                </div>

                                                <div class="reward-actions">
                                                    <button type="button"
                                                            class="btn btn-success btn-sm"
                                                            :disabled="!canRedeem(r) || isRedeemingId === r.id"
                                                            @click.prevent="redeem(r)">
                                                        <span v-if="isRedeemingId === r.id" class="spinner-border spinner-border-sm me-1"></span>
                                                        @{{ isRedeemingId === r.id ? 'กำลังทำรายการ…' : 'แลกเลย' }}
                                                    </button>

                                                    <div class="reward-hint">
                                                        <div class="text-warning small" v-if="diamond < Number(r.point_cost)">พ้อยท์ไม่พอ</div>
                                                        <div class="text-muted small" v-else-if="!isActive(r)">ไม่พร้อมใช้งาน</div>
                                                        <div class="text-muted small" v-else-if="isOutOfStock(r)">สต๊อกหมด</div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </template>

                            <!-- =========================
                                 TAB: HISTORY
                            ========================== -->
                            <template v-else>

                                <!-- LOADING -->
                                <div class="text-center text-light py-4" v-if="loadingHistory">
                                    <div class="spinner-border spinner-border-sm text-light"></div>
                                    <div class="mt-2">กำลังโหลดประวัติ…</div>
                                </div>

                                <!-- EMPTY -->
                                <div class="text-center text-light py-5" v-else-if="!historyItems.length">
                                    <i class="fa fa-history text-info fs-2 mb-2"></i>
                                    <p class="mb-0">ยังไม่มีประวัติการแลก</p>
                                    <p class="text-muted small mb-0">เมื่อคุณแลกรางวัล ระบบจะแสดงรายการไว้ที่นี่</p>
                                </div>

                                <!-- LIST -->
                                <div v-else class="reward-grid">
                                    <div class="col-12" v-for="h in historyItems" :key="h.id || (h.code + '-' + h.created_at)">

                                        <div class="reward-card-inner reward-history-card">
                                            <div class="reward-thumb placeholder">
                                                <i class="fa fa-receipt"></i>
                                            </div>

                                            <div class="reward-content">
                                                <div class="reward-toprow">
                                                    <div class="reward-title" v-text="historyTitle(h)"></div>
                                                    <div class="reward-cost">
                                                        ใช้ <span class="fw-bold">@{{ Number(historyPointCost(h)).toLocaleString() }}</span> พ้อยท์
                                                    </div>
                                                </div>

                                                <div class="reward-desc" v-if="historyDesc(h)" v-text="historyDesc(h)"></div>

                                                <div class="reward-benefit">
                                                    <span class="text-muted">ผลลัพธ์:</span>
                                                    <span class="ms-1" :class="historyStatusClass(h)" v-text="historyStatusText(h)"></span>
                                                </div>

                                                <div class="reward-badges">
                                                    <span class="badge bg-secondary" v-text="historyTypeText(h)"></span>
                                                    <span class="badge bg-dark border text-muted" v-if="historyCode(h)" v-text="historyCode(h)"></span>
                                                    <span class="badge bg-dark border text-muted" v-if="historyWhen(h)" v-text="historyWhen(h)"></span>
                                                </div>

                                                <div class="reward-actions">
                                                    <button type="button"
                                                            class="btn btn-outline-light btn-sm"
                                                            @click.prevent="openHistoryDetail(h)">
                                                        รายละเอียด
                                                    </button>

                                                    <div class="reward-hint">
                                                        <div class="text-muted small" v-if="historyRef(h)">
                                                            Ref: @{{ historyRef(h) }}
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </template>

                        </div>

                        <!-- ===== Sticky Footer (Pagination) ===== -->
                        <div class="reward-sticky bottom" v-if="activeTab === 'rewards' && totalPages > 1 && !loading">
                            <hr class="m-0 opacity-25">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2">
                                <div class="text-muted small">
                                    แสดง @{{ pageFrom }}–@{{ pageTo }} / @{{ meta.total }}
                                    <span class="ms-2" v-if="hasAnyFilter">
                                        (หลังกรอง: @{{ displayCount }} รายการ)
                                    </span>
                                </div>

                                <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
                                    <button type="button"
                                            class="btn btn-outline-light"
                                            :disabled="page <= 1"
                                            @click.prevent="gotoPage(page - 1)">
                                        ก่อนหน้า
                                    </button>

                                    <button type="button"
                                            class="btn btn-outline-light"
                                            v-for="p in pageNumbers"
                                            :key="p"
                                            :class="{ 'active': p === page }"
                                            @click.prevent="gotoPage(p)">
                                        @{{ p }}
                                    </button>

                                    <button type="button"
                                            class="btn btn-outline-light"
                                            :disabled="page >= totalPages"
                                            @click.prevent="gotoPage(page + 1)">
                                        ถัดไป
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ===== Sticky Footer (Pagination) - HISTORY ===== -->
                        <div class="reward-sticky bottom" v-if="activeTab === 'history' && historyTotalPages > 1 && !loadingHistory">
                            <hr class="m-0 opacity-25">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2">
                                <div class="text-muted small">
                                    แสดง @{{ historyPageFrom }}–@{{ historyPageTo }} / @{{ historyMeta.total }}
                                </div>

                                <div class="btn-group btn-group-sm" role="group" aria-label="Pagination">
                                    <button type="button"
                                            class="btn btn-outline-light"
                                            :disabled="historyPage <= 1"
                                            @click.prevent="gotoHistoryPage(historyPage - 1)">
                                        ก่อนหน้า
                                    </button>

                                    <button type="button"
                                            class="btn btn-outline-light"
                                            v-for="p in historyPageNumbers"
                                            :key="p"
                                            :class="{ 'active': p === historyPage }"
                                            @click.prevent="gotoHistoryPage(p)">
                                        @{{ p }}
                                    </button>

                                    <button type="button"
                                            class="btn btn-outline-light"
                                            :disabled="historyPage >= historyTotalPages"
                                            @click.prevent="gotoHistoryPage(historyPage + 1)">
                                        ถัดไป
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        กลับ
                    </button>
                </div>

            </div>
        </div>
    </div>
</script>

@push('components')
    <style>
        .reward-modal { min-height: 60vh; }

        .reward-shell{
            position: relative;
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* ✅ กัน modal/ลูก ๆ แย่ง scroll */
        }

        .reward-sticky{
            position: sticky;
            z-index: 10;
            background: rgba(20, 20, 24, .92);
            backdrop-filter: blur(6px);
        }
        .reward-sticky.top{ top: 0; }
        .reward-sticky.bottom{ bottom: 0; }

        /* ✅ FIX: ให้ scroll อยู่ชั้นเดียว + รองรับ touch */
        .reward-scroll{
            flex: 1;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            max-height: calc(60vh - 150px);
        }

        .reward-subhint { line-height: 1.2; }

        .reward-grid { display: grid; gap: .75rem; }
        .reward-card-inner{
            display:flex; gap:.75rem;
            padding:.75rem;
            border-radius:14px;
            background: linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
            border: 1px solid rgba(255,255,255,.06);
            box-shadow: 0 10px 20px rgba(0,0,0,.18);
        }

        .reward-thumb{
            width:78px; height:78px;
            border-radius:14px;
            overflow:hidden;
            flex:0 0 auto;
            background: rgba(255,255,255,.06);
            display:flex; align-items:center; justify-content:center;
        }
        .reward-thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
        .reward-thumb.placeholder i{ color:rgba(255,255,255,.35); font-size:28px; }

        .reward-content{ flex:1; min-width:0; }
        .reward-toprow{ display:flex; justify-content:space-between; gap:.75rem; align-items:flex-start; }

        .reward-title{
            color:#fff; font-weight:800; line-height:1.1;
            max-width:68%;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            letter-spacing: .2px;
        }

        .reward-cost{
            font-size:.85rem; padding:.25rem .55rem; border-radius:999px;
            background: #f0c04a; color:#111; white-space:nowrap;
            box-shadow: 0 6px 14px rgba(240,192,74,.18);
        }

        .reward-desc{
            margin-top:.35rem; color:rgba(255,255,255,.60);
            font-size:.86rem; line-height:1.25;
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
        }

        .reward-benefit{ margin-top:.35rem; color:rgba(255,255,255,.92); font-size:.92rem; }

        .reward-badges{
            margin-top:.5rem;
            display:flex; gap:.35rem; flex-wrap:wrap;
        }

        .badge-featured{
            background: linear-gradient(180deg, rgba(255,206,75,1), rgba(240,160,50,1));
            color:#111;
        }

        .reward-actions{
            margin-top:.7rem;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:.75rem;
        }
        .reward-hint{ text-align:right; min-width:96px; }

        .btn-group .btn.active{
            background: rgba(255,255,255,.12);
            border-color: rgba(255,255,255,.25);
        }

        /* ===== Premium UI helpers ===== */
        .reward-pill{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            padding:.18rem .5rem;
            border-radius:999px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            font-size:.82rem;
            color: rgba(255,255,255,.85);
        }

        .reward-seg{
            display:inline-flex;
            padding:.18rem;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 999px;
            gap:.2rem;
        }
        .reward-seg-btn{
            border: 0;
            background: transparent;
            color: rgba(255,255,255,.78);
            padding: .35rem .7rem;
            border-radius: 999px;
            font-size: .85rem;
            line-height: 1;
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            transition: all .15s ease;
            user-select:none;
        }
        .reward-seg-btn:hover{
            color:#fff;
            background: rgba(255,255,255,.06);
        }
        .reward-seg-btn.active{
            color:#111;
            background: linear-gradient(180deg, rgba(255,206,75,1), rgba(240,160,50,1));
            box-shadow: 0 10px 18px rgba(240,160,50,.16);
        }
        .reward-seg-count{
            margin-left:.25rem;
            background: rgba(0,0,0,.22);
            color:#111;
            padding: .08rem .4rem;
            border-radius: 999px;
            font-weight: 800;
            font-size: .75rem;
        }

        .reward-chips{
            display:flex;
            flex-wrap:wrap;
            gap:.35rem;
        }
        .chip{
            display:inline-flex;
            align-items:center;
            gap:.35rem;
            padding:.25rem .55rem;
            border-radius:999px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
            color: rgba(255,255,255,.85);
            font-size:.82rem;
        }
        .chip-x{
            border:0;
            background: rgba(255,255,255,.10);
            color: rgba(255,255,255,.85);
            width: 18px;
            height: 18px;
            border-radius: 999px;
            line-height: 18px;
            padding:0;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
        }
        .chip-x:hover{
            background: rgba(255,255,255,.18);
            color:#fff;
        }

        /* ✅ NEW: Tabs */
        .reward-tabs{
            display:flex;
            gap:.35rem;
            padding:.25rem;
            border-radius: 999px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.10);
        }
        .reward-tab-btn{
            border:0;
            background: transparent;
            color: rgba(255,255,255,.80);
            padding: .45rem .85rem;
            border-radius: 999px;
            font-weight: 800;
            font-size: .85rem;
            display:inline-flex;
            align-items:center;
            transition: all .15s ease;
            user-select:none;
        }
        .reward-tab-btn:hover{
            background: rgba(255,255,255,.06);
            color:#fff;
        }
        .reward-tab-btn.active{
            color:#111;
            background: linear-gradient(180deg, rgba(255,206,75,1), rgba(240,160,50,1));
            box-shadow: 0 10px 18px rgba(240,160,50,.16);
        }
        .reward-tab-count{
            margin-left:.4rem;
            background: rgba(0,0,0,.22);
            color:#111;
            padding: .08rem .45rem;
            border-radius: 999px;
            font-weight: 900;
            font-size: .75rem;
        }

        .reward-history-card .reward-cost{
            background: rgba(255,255,255,.10);
            color: rgba(255,255,255,.92);
            box-shadow: none;
        }

        /* Mobile fine-tune */
        @media (max-width: 420px) {
            .reward-title { max-width: 62%; }
            .reward-scroll { max-height: calc(60vh - 160px); }
        }
    </style>

    <style>
        /* =========================
   Bootstrap 4 Compatibility Layer
   Scope: Reward Modal only
========================= */
        .reward-modal, #rewardModal {
            /* ป้องกันฟอนต์/line-height บางอย่างใน reboot ต่างกัน */
            font-smoothing: antialiased;
        }

        /* --- BS5 spacing alias: ms/me -> margin-start/end --- */
        .reward-modal .ms-1, #rewardModal .ms-1 { margin-left: .25rem !important; }
        .reward-modal .ms-2, #rewardModal .ms-2 { margin-left: .5rem !important; }
        .reward-modal .ms-3, #rewardModal .ms-3 { margin-left: 1rem !important; }

        .reward-modal .me-1, #rewardModal .me-1 { margin-right: .25rem !important; }
        .reward-modal .me-2, #rewardModal .me-2 { margin-right: .5rem !important; }
        .reward-modal .me-3, #rewardModal .me-3 { margin-right: 1rem !important; }

        /* --- BS5 gap utility (BS4 ไม่มี) --- */
        .reward-modal .gap-1, #rewardModal .gap-1 { gap: .25rem; }
        .reward-modal .gap-2, #rewardModal .gap-2 { gap: .5rem; }
        .reward-modal .gap-3, #rewardModal .gap-3 { gap: 1rem; }

        /* fallback สำหรับ flex gap */
        .reward-modal .d-flex.gap-2 > * + *, #rewardModal .d-flex.gap-2 > * + * { margin-left: .5rem; }
        .reward-modal .d-flex.gap-1 > * + *, #rewardModal .d-flex.gap-1 > * + * { margin-left: .25rem; }

        /* --- BS5 gutter utility g-2/gx-2/gy-2 (BS4 ไม่มี) --- */
        .reward-modal .row.g-2, #rewardModal .row.g-2 { margin-right: -.25rem; margin-left: -.25rem; }
        .reward-modal .row.g-2 > [class*="col-"], #rewardModal .row.g-2 > [class*="col-"] {
            padding-right: .25rem; padding-left: .25rem;
        }
        .reward-modal .row.g-2 { margin-top: -.25rem; }
        .reward-modal .row.g-2 > [class*="col-"] { margin-top: .25rem; }

        /* --- form-select (BS5) --- */
        .reward-modal .form-select, #rewardModal .form-select {
            display: block;
            width: 100%;
            height: calc(1.5em + .5rem + 2px);
            padding: .25rem .75rem;
            font-size: .875rem;
            line-height: 1.5;
            color: #f8f9fa;
            background-color: #1f232a;
            background-clip: padding-box;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: .2rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image:
                    linear-gradient(45deg, transparent 50%, rgba(255,255,255,.55) 50%),
                    linear-gradient(135deg, rgba(255,255,255,.55) 50%, transparent 50%);
            background-position:
                    calc(100% - 16px) 50%,
                    calc(100% - 11px) 50%;
            background-size: 5px 5px, 5px 5px;
            background-repeat: no-repeat;
        }
        .reward-modal .form-select:focus, #rewardModal .form-select:focus {
            outline: 0;
            box-shadow: 0 0 0 .2rem rgba(240,192,74,.15);
            border-color: rgba(240,192,74,.35);
        }

        /* --- btn-close (BS5) --- */
        .reward-modal .btn-close, #rewardModal .btn-close {
            box-sizing: content-box;
            width: 1em;
            height: 1em;
            padding: .25em;
            color: #fff;
            border: 0;
            border-radius: .25rem;
            opacity: .75;
            background: transparent;
            position: relative;
        }
        .reward-modal .btn-close:before, #rewardModal .btn-close:before {
            content: "×";
            font-size: 1.25rem;
            line-height: 1;
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reward-modal .btn-close:hover, #rewardModal .btn-close:hover { opacity: 1; }

        .reward-modal .reward-sticky, #rewardModal .reward-sticky {
            position: sticky;
        }

        #rewardModal .modal-content {
            border: 1px solid rgba(255,255,255,.08);
        }
        #rewardModal .modal-header, #rewardModal .modal-footer {
            border-color: rgba(255,255,255,.08);
        }

        .reward-modal .opacity-25, #rewardModal .opacity-25 { opacity: .25 !important; }
    </style>

    <script type="module">
        Vue.component('reward-modal', {
            template: '#reward-modal-template',
            data() {
                return {
                    // ===== TAB CONTROL =====
                    activeTab: 'rewards', // 'rewards' | 'history'

                    // ===== REWARDS =====
                    rewards: [],
                    diamond: 0,
                    loading: false,
                    isRequesting: false,
                    isRedeemingId: null,
                    idemKey: null,

                    page: 1,
                    perPage: 2,

                    meta: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 2,
                        total: 0,
                        from: 0,
                        to: 0
                    },

                    // ✅ filters
                    filters: {
                        q: '',
                        reward_type: '',
                        featured_only: false, // ✅ NEW
                    },

                    _filterTimer: null,

                    // ===== HISTORY =====
                    historyItems: [],
                    loadingHistory: false,
                    historyPage: 1,
                    historyPerPage: 10,
                    historyMeta: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0,
                        from: 0,
                        to: 0
                    },
                };
            },
            computed: {
                // ===== Rewards pagination =====
                totalPages() {
                    return Math.max(1, Number(this.meta?.last_page || 1));
                },
                pageFrom() {
                    return Number(this.meta?.from || 0);
                },
                pageTo() {
                    return Number(this.meta?.to || 0);
                },
                pageNumbers() {
                    const total = this.totalPages;
                    const current = Number(this.page || 1);
                    const max = 5;

                    if (total <= max) return Array.from({ length: total }, (_, i) => i + 1);

                    let start = Math.max(1, current - 2);
                    let end = start + (max - 1);
                    if (end > total) { end = total; start = end - (max - 1); }

                    const pages = [];
                    for (let p = start; p <= end; p++) pages.push(p);
                    return pages;
                },

                // ✅ NEW: มี filter ใด ๆ ไหม
                hasAnyFilter() {
                    return !!(this.filters.featured_only || this.filters.reward_type || this.filters.q);
                },

                // ✅ NEW: นับจำนวนแนะนำในชุดข้อมูลที่โหลดมา (ก่อนกรองอื่น)
                featuredCount() {
                    if (!Array.isArray(this.rewards)) return 0;
                    return this.rewards.reduce((acc, it) => acc + (Number(it?.is_featured || 0) === 1 ? 1 : 0), 0);
                },

                // ✅ NEW: กรองตาม filter (ทั้ง server และ fallback client-side)
                filteredRewards() {
                    let list = Array.isArray(this.rewards) ? [...this.rewards] : [];

                    const q = (this.filters.q || '').toString().trim().toLowerCase();
                    const type = (this.filters.reward_type || '').toString().trim();
                    const featuredOnly = !!this.filters.featured_only;

                    if (featuredOnly) {
                        list = list.filter(it => Number(it?.is_featured || 0) === 1);
                    }

                    if (type) {
                        list = list.filter(it => (it?.reward_type || '') === type);
                    }

                    if (q) {
                        list = list.filter(it => {
                            const name = (it?.name || '').toString().toLowerCase();
                            const desc = (it?.description || '').toString().toLowerCase();
                            const code = (it?.code || '').toString().toLowerCase();
                            return name.includes(q) || desc.includes(q) || code.includes(q);
                        });
                    }

                    return list;
                },

                // ✅ NEW: เรียง “แนะนำก่อน” เสมอ (กัน backend/สภาพแวดล้อมเปลี่ยน)
                sortedRewards() {
                    return [...this.filteredRewards].sort((a, b) => {
                        // 1) is_featured desc
                        const fa = Number(a?.is_featured || 0);
                        const fb = Number(b?.is_featured || 0);
                        if (fa !== fb) return fb - fa;

                        // 2) priority desc
                        const pa = Number(a?.priority || 0);
                        const pb = Number(b?.priority || 0);
                        if (pa !== pb) return pb - pa;

                        // 3) id desc
                        return Number(b?.id || 0) - Number(a?.id || 0);
                    });
                },

                // ✅ ใช้ตัวนี้ใน UI
                displayRewards() {
                    return this.sortedRewards;
                },

                // ✅ จำนวนหลังกรอง (ใช้โชว์ใน header/footer)
                displayCount() {
                    return Number(this.displayRewards?.length || 0).toLocaleString();
                },

                // ===== History pagination =====
                historyTotalPages() {
                    return Math.max(1, Number(this.historyMeta?.last_page || 1));
                },
                historyPageFrom() {
                    return Number(this.historyMeta?.from || 0);
                },
                historyPageTo() {
                    return Number(this.historyMeta?.to || 0);
                },
                historyPageNumbers() {
                    const total = this.historyTotalPages;
                    const current = Number(this.historyPage || 1);
                    const max = 5;

                    if (total <= max) return Array.from({ length: total }, (_, i) => i + 1);

                    let start = Math.max(1, current - 2);
                    let end = start + (max - 1);
                    if (end > total) { end = total; start = end - (max - 1); }

                    const pages = [];
                    for (let p = start; p <= end; p++) pages.push(p);
                    return pages;
                },
                historyDisplayCount() {
                    return Number(this.historyItems?.length || 0).toLocaleString();
                },
            },
            watch: {
                rewards() { this.clampPage(); }
            },
            mounted() {
                const el = this.$refs.rewardModal;
                if (!el) return;

                el.addEventListener('shown.bs.modal', () => {
                    // default เปิดที่แท็บรางวัล
                    this.activeTab = 'rewards';
                    this.loadRewards(1);
                });
            },
            methods: {
                // ===== TAB =====
                setTab(tab) {
                    const next = (tab === 'history') ? 'history' : 'rewards';
                    if (this.activeTab === next) return;

                    this.activeTab = next;

                    // ปรับ scroll ให้กลับขึ้นบนแบบสุภาพ (optional)
                    this.$nextTick(() => {
                        const sc = this.$el?.querySelector?.('.reward-scroll');
                        if (sc) sc.scrollTop = 0;
                    });

                    if (this.activeTab === 'history') {
                        // โหลด history ครั้งแรกหรือทุกครั้งก็ได้
                        this.loadHistory(1);
                    } else {
                        // กลับมารางวัล ไม่ต้อง reload เสมอ แต่ให้ clamp ตาม meta
                        this.clampPage();
                    }
                },

                refreshActiveTab() {
                    if (this.activeTab === 'history') return this.loadHistory(this.historyPage || 1);
                    return this.loadRewards(this.page || 1);
                },

                // ===== UI helpers (Rewards) =====
                setFeaturedOnly(v) {
                    const next = !!v;
                    if (this.filters.featured_only === next) return;
                    this.filters.featured_only = next;
                    this.onFilterChanged();
                },

                labelTypeChip(type) {
                    if (type === 'wallet_credit') return 'เครดิต';
                    if (type === 'wallet_gem') return 'เพชร';
                    if (type === 'external') return 'ของรางวัล';
                    return 'ทั้งหมด';
                },

                limitBadge(r) {
                    if (!r || !r.limit_type || r.limit_type === 'unlimited') {
                        return '';
                    }

                    if (r.limit_type === 'per_reward') {
                        const n = Number(r.limit_per_user || 1);
                        return `รับได้ ${n} ครั้ง`;
                    }

                    if (r.limit_type === 'per_period') {
                        const n = Number(r.limit_per_period || 1);
                        const map = { day: 'วัน', week: 'สัปดาห์', month: 'เดือน' };
                        const p = map[r.limit_period] || '';
                        return `รับได้ ${n} ครั้ง / ${p}`;
                    }

                    return '';
                },

                clampPage() {
                    const total = this.totalPages;
                    if (this.page > total) this.page = total;
                    if (this.page < 1) this.page = 1;
                },

                gotoPage(p) {
                    const n = Number(p);
                    if (!Number.isFinite(n)) return;

                    const target = Math.min(Math.max(n, 1), this.totalPages);
                    if (target === this.page) return;

                    this.page = target;
                    this.loadRewards(this.page);
                },

                onFilterChanged() {
                    clearTimeout(this._filterTimer);
                    this._filterTimer = setTimeout(() => {
                        this.loadRewards(1);
                    }, 350);
                },

                clearFilter() {
                    this.filters.q = '';
                    this.filters.reward_type = '';
                    this.filters.featured_only = false;
                    this.loadRewards(1);
                },

                // ===== API: Rewards =====
                async loadRewards(page = this.page) {
                    this.loading = true;
                    this.page = Number(page || 1);

                    try {
                        const res = await axios.post(
                            "{{ route('customer.reward.list') }}",
                            {
                                page: this.page,
                                per_page: this.perPage,

                                // ✅ ส่ง filter ไปด้วย (backend รองรับอยู่แล้ว: q/reward_type)
                                q: this.filters.q || '',
                                reward_type: this.filters.reward_type || '',
                                // ✅ featured_only ยังไม่ได้รองรับฝั่ง backend ก็ไม่เป็นไร เรากรอง client-side อยู่แล้ว
                                featured_only: this.filters.featured_only ? 1 : 0,
                            },
                            { timeout: 10000 }
                        );

                        if (res.data?.success) {
                            const list = Array.isArray(res.data.rewards) ? res.data.rewards : [];
                            this.rewards = list;
                            this.diamond = Number(res.data.diamond || 0);

                            // meta จาก server เป็นหลัก
                            this.meta = res.data.meta || this.meta;
                            this.page = Number(this.meta.current_page || this.page || 1);
                            this.perPage = Number(this.meta.per_page || this.perPage);
                        } else {
                            this.rewards = [];
                            this.meta = { ...this.meta, current_page: 1, last_page: 1, total: 0, from: 0, to: 0 };
                            this.page = 1;
                        }
                    } catch (e) {
                        console.error('reward.list error:', e);
                        this.rewards = [];
                    } finally {
                        this.loading = false;
                    }
                },

                async redeem(r) {
                    if (!this.canRedeem(r)) return;

                    this.isRedeemingId = r.id;
                    this.idemKey = (window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`);

                    try {
                        const res = await axios.post(
                            "{{ route('customer.reward.redeem') }}",
                            { reward_id: r.id },
                            { headers: { 'X-Idempotency-Key': this.idemKey }, timeout: 15000 }
                        );

                        if (res.data?.success) {
                            this.diamond = Number(res.data.diamond || this.diamond);

                            Swal.fire({
                                title: res.data.format?.title || 'สำเร็จ',
                                text: res.data.format?.msg || 'แลกรางวัลเรียบร้อย',
                                icon: 'success'
                            });

                            await this.loadRewards(this.page);

                            // ✅ ถ้าต้องการ: หลังแลกสำเร็จ สลับไปแท็บประวัติให้เลยก็ทำได้
                            // this.setTab('history');

                            if (window.reLoadCredit) window.reLoadCredit();
                        } else {
                            Swal.fire('ไม่สำเร็จ', res.data?.message || 'ไม่สามารถแลกได้', 'warning');
                        }
                    } catch (e) {
                        Swal.fire('ผิดพลาด', e?.response?.data?.message || 'ไม่สามารถแลกรางวัลได้', 'error');
                    } finally {
                        this.isRedeemingId = null;
                        this.idemKey = null;
                    }
                },

                // ===== API: History =====
                async loadHistory(page = this.historyPage) {
                    this.loadingHistory = true;
                    this.historyPage = Number(page || 1);

                    try {
                        // ✅ แนะนำให้ทำ route นี้ใน backend
                        // response แนะนำ: { success: true, items: [...], meta: {...} }
                        const res = await axios.post(
                            "{{ route('customer.reward.history') }}",
                            {
                                page: this.historyPage,
                                per_page: this.historyPerPage,
                            },
                            { timeout: 10000 }
                        );

                        if (res.data?.success) {
                            this.historyItems = Array.isArray(res.data.items) ? res.data.items : [];
                            this.historyMeta = res.data.meta || this.historyMeta;

                            this.historyPage = Number(this.historyMeta.current_page || this.historyPage || 1);
                            this.historyPerPage = Number(this.historyMeta.per_page || this.historyPerPage);
                        } else {
                            this.historyItems = [];
                            this.historyMeta = { ...this.historyMeta, current_page: 1, last_page: 1, total: 0, from: 0, to: 0 };
                            this.historyPage = 1;
                        }
                    } catch (e) {
                        // ถ้า backend ยังไม่มี route นี้ จะ 404 -> ให้เงียบ ๆ แล้วโชว์ empty state
                        console.warn('reward.history not ready:', e);
                        this.historyItems = [];
                        this.historyMeta = { ...this.historyMeta, current_page: 1, last_page: 1, total: 0, from: 0, to: 0 };
                        this.historyPage = 1;
                    } finally {
                        this.loadingHistory = false;
                    }
                },

                gotoHistoryPage(p) {
                    const n = Number(p);
                    if (!Number.isFinite(n)) return;

                    const target = Math.min(Math.max(n, 1), this.historyTotalPages);
                    if (target === this.historyPage) return;

                    this.historyPage = target;
                    this.loadHistory(this.historyPage);
                },

                openHistoryDetail(h) {
                    // ไม่ทำ modal ซ้อนให้รก ใช้ Swal แบบเบา ๆ
                    Swal.fire({
                        title: this.historyTitle(h) || 'รายละเอียด',
                        html: `
                            <div style="text-align:left">
                                <div><b>สถานะ:</b> ${this.escapeHtml(this.historyStatusText(h))}</div>
                                <div><b>ประเภท:</b> ${this.escapeHtml(this.historyTypeText(h))}</div>
                                <div><b>ใช้พ้อยท์:</b> ${this.escapeHtml(String(this.historyPointCost(h) || 0))}</div>
                                ${this.historyRef(h) ? `<div><b>Ref:</b> ${this.escapeHtml(this.historyRef(h))}</div>` : ``}
                                ${this.historyCode(h) ? `<div><b>โค้ด:</b> ${this.escapeHtml(this.historyCode(h))}</div>` : ``}
                                ${this.historyWhen(h) ? `<div><b>เมื่อ:</b> ${this.escapeHtml(this.historyWhen(h))}</div>` : ``}
                            </div>
                        `,
                        icon: 'info'
                    });
                },

                escapeHtml(str) {
                    return String(str || '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');
                },

                // ===== Logic (Rewards) =====
                isActive(r) { return String(r.status || '').toLowerCase() === 'active'; },
                isOutOfStock(r) {
                    if (Number(r.stock_unlimited) === 1) return false;
                    return (Number(r.stock || 0) - Number(r.reserved_stock || 0)) <= 0;
                },
                canRedeem(r) {
                    if (this.loading) return false;
                    if (!this.isActive(r)) return false;
                    if (this.isOutOfStock(r)) return false;
                    return Number(this.diamond) >= Number(r.point_cost || 0);
                },

                labelMode(r) {
                    if (r.fulfillment_mode === 'auto') return 'Auto';
                    if (r.fulfillment_mode === 'manual') return 'Manual';
                    if (r.fulfillment_mode === 'approval') return 'Approval';
                    return r.fulfillment_mode || '-';
                },
                stockText(r) {
                    if (Number(r.stock_unlimited) === 1) return 'สต๊อกไม่จำกัด';
                    const remain = (Number(r.stock || 0) - Number(r.reserved_stock || 0));
                    return 'คงเหลือ ' + Math.max(remain, 0);
                },

                formatMoney(v) {
                    const n = Number(v || 0);
                    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                formatInt(v) {
                    const n = Number(v || 0);
                    return n.toLocaleString();
                },

                imageSrc(r) {
                    const raw = (r?.image_url || r?.image || '').toString().trim();
                    if (!raw) return '';
                    if (/^https?:\/\//i.test(raw)) return raw;
                    if (/^\/\//.test(raw)) return window.location.protocol + raw;
                    if (raw.startsWith('/')) return raw;

                    const base = (this.$root?.baseUrl || '').toString().replace(/\/+$/,'');
                    const origin = window.location.origin.replace(/\/+$/,'');
                    const prefix = base || origin;

                    return prefix + '/' + raw.replace(/^\/+/,'');
                },

                trans(key) {
                    if (typeof window.translations_home === 'object' && window.translations_home) {
                        return window.translations_home[key] || key;
                    }
                    return key;
                },

                // ===== History mapping helpers (รองรับชื่อ field ที่หลากหลาย) =====
                historyTitle(h) {
                    return (
                        h?.reward_name_snapshot ||
                        h?.reward_name ||
                        h?.reward?.name ||
                        h?.name ||
                        'รายการแลกรางวัล'
                    );
                },
                historyDesc(h) {
                    return (
                        h?.reward_description_snapshot ||
                        h?.description ||
                        h?.reward?.description ||
                        ''
                    );
                },
                historyPointCost(h) {
                    return Number(
                        h?.point_cost_snapshot ??
                        h?.point_cost ??
                        h?.reward_point_cost ??
                        0
                    );
                },
                historyTypeText(h) {
                    const t = (h?.reward_type_snapshot || h?.reward_type || h?.type || '').toString();
                    if (t === 'wallet_credit') return 'เครดิต';
                    if (t === 'wallet_gem') return 'เพชร';
                    if (t === 'external') return 'ของรางวัล';
                    return t || 'ไม่ระบุ';
                },
                historyStatusText(h) {
                    const s = (h?.status || '').toString().toLowerCase();
                    if (!s) return 'ไม่ระบุ';
                    if (s === 'approved' || s === 'success' || s === 'completed') return 'สำเร็จ';
                    if (s === 'pending' || s === 'waiting') return 'รอดำเนินการ';
                    if (s === 'rejected' || s === 'cancelled' || s === 'canceled' || s === 'failed') return 'ไม่สำเร็จ';
                    return h?.status || 'ไม่ระบุ';
                },
                historyStatusClass(h) {
                    const s = (h?.status || '').toString().toLowerCase();
                    if (s === 'approved' || s === 'success' || s === 'completed') return 'text-success fw-bold';
                    if (s === 'pending' || s === 'waiting') return 'text-warning fw-bold';
                    if (s === 'rejected' || s === 'cancelled' || s === 'canceled' || s === 'failed') return 'text-danger fw-bold';
                    return 'text-light';
                },
                historyCode(h) {
                    return (
                        h?.reward_code_snapshot ||
                        h?.reward_code ||
                        h?.reward?.code ||
                        ''
                    );
                },
                historyWhen(h) {
                    // รองรับ created_at / date_create / redeemed_at
                    const raw = h?.redeemed_at || h?.created_at || h?.date_create || '';
                    if (!raw) return '';
                    return String(raw).replace('T', ' ').slice(0, 19);
                },
                historyRef(h) {
                    return (h?.reference || h?.ref || h?.code || h?.id || '').toString();
                },
            }
        });
    </script>
@endpush
