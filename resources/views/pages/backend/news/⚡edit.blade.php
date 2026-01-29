<?php

use App\Livewire\Backend\Traits\HasImageRelationsTrait;
use App\Livewire\Backend\Traits\SetupTrait;
use App\Models\News;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.gd-admin')] class extends Component
{
    // 使用通用的 SetupTrait
    use HasImageRelationsTrait, SetupTrait;

    #[Locked]
    public $id; // 當前資料的唯一識別碼

    public $title = ''; // 資料的標題

    public $body = ''; // 資料的內容

    public $locale = 'zh_TW'; // 語系設定，預設為繁體中文

    public $sortNumber = 1; // 排序編號，預設為 1

    public $isActive = true; // 是否啟用，預設為啟用

    public array $images = []; // 上傳的圖片陣列

    public int $maxImageSize = 3072; // 最大圖片大小，單位為 KB

    // 多傳記得要改1以上
    public int $maxImages = 5; // 單張圖片上傳設定為 1

    public ?int $returnPage = null; // 返回的頁碼，用於重定向回列表頁面

    /**
     * 初始化元件，根據是否為新增或編輯模式進行設置
     *
     * @param  mixed  $id  資料的唯一識別碼
     * @param  string|null  $parentTitle  父層標題
     * @param  array  $locales  可用的語系列表
     */
    public function mount($id = null, $parentTitle = null, $locales = [])
    {
        $this->id = $id;
        $this->returnPage = request()->query('page');

        // 設置通用的 trait 屬性
        $this->setupTrait(
            currentModel: new News, // 當前模型
            pageTitle: $this->isCreating() ? '新增' : '編輯', // 頁面標題
            parentTitle: $parentTitle, // 父層標題
            locales: $locales, // 可用的語系列表
        );

        // 載入編輯模式的資料
        $this->loadEditData();
    }

    /**
     * 根據模式載入資料
     */
    protected function loadEditData(): void
    {
        if ($this->isCreating()) {
            // 新增模式下，設置預設語系
            $defaultLocale = array_key_first($this->traitLocales) ?? 'zh_TW';
            $traitLocale = request()->input('locale', $defaultLocale);
            $this->locale = $traitLocale === 'All' || empty($traitLocale) ? 'zh_TW' : $traitLocale;

            return;
        }

        // 編輯模式下，從資料庫載入資料
        $model = $this->traitModel::query()->findOrFail($this->id);
        $this->fillFromModel($model);
    }

    /**
     * 將資料庫模型的值填充到元件屬性中
     *
     * @param  mixed  $model  資料庫模型(綁定任意模型如 News Products)
     */
    protected function fillFromModel($model): void
    {
        $this->title = $model->title;
        $this->body = $model->body;
        $this->locale = $model->locale;
        $this->sortNumber = $model->sort_number;
        $this->isActive = $model->is_active;
        // $this->images = $model->image_url ? (array) $model->image_url : []; // 單張
        $this->images = $model->newsImages?->pluck('image_url')?->toArray() ?? []; // 多張
    }

    /**
     * 驗證並返回表單資料
     */
    protected function getValidatedData(): array
    {
        // 驗證表單輸入
        $this->validate([
            'title' => ['required', 'max:255'], // 標題為必填，且長度不得超過 255 字元
            'body' => ['nullable', 'string'], // 內容為可選，且必須為字串
            'locale' => ['required', 'string'], // 語系為必填，且必須為字串
            'sortNumber' => ['required', 'integer', 'min:1'], // 排序編號為必填，且必須為大於等於 1 的整數
            'isActive' => ['boolean'], // 啟用狀態必須為布林值
        ]);

        // 如果圖片不要必填，註解掉這行
        $this->validate(
            [
                'images' => ['required', 'array', 'min:1'],
                'images.*' => ['string'],
            ],
            [
                'images.required' => '請上傳圖片',
                'images.min' => '至少需要一張圖片',
            ],
        );

        // 返回驗證後的資料
        return [
            'title' => $this->title,
            'body' => $this->body,
            'image_url' => $this->images[0] ?? null,
            'locale' => $this->locale,
            'sort_number' => $this->sortNumber,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * 儲存資料，根據模式執行新增或更新操作
     */
    public function save()
    {
        $data = $this->getValidatedData(); // 獲取驗證後的資料
        $message = $this->isCreating() ? '新增成功' : '編輯成功'; // 設置操作成功訊息

        if ($this->isCreating()) {
            // 新增模式，創建新資料
            $model = $this->traitModel::query()->create($data);
        } else {
            // 編輯模式，更新現有資料
            $model = $this->traitModel::query()->findOrFail($this->id);
            $model->update($data);
        }

        // 同步關聯圖片（單傳時註解）
        $this->syncImageRelation(model: $model, images: $this->images, relation: 'newsImages');

        // 設置成功訊息到 session
        session()->flash('gd-session-message', $message);

        // 重定向到新聞列表頁面（保留分頁）
        return redirect()->route(
            'gd-admin.news.index',
            array_filter(
                [
                    'page' => $this->returnPage,
                ],
                fn ($value) => $value !== null,
            ),
        );
    }

    /**
     * 判斷是否為新增模式
     */
    protected function isCreating(): bool
    {
        return $this->id === null || $this->id === 'add';
    }
};
?>

<div>
    <main class="w-full mx-auto space-y-4">
        <h1>{{ $traitPageTitle }}</h1>
        <form wire:submit.prevent="save" class="block lg:flex lg:gap-x-10 space-y-10 lg:space-y-0">
            <!-- LEFT SIDE -->
            <section class="space-y-4 flex-1 min-w-0">



                {{-- 多傳圖片 $maxImages(改為1的時候就是單傳圖片) --}}
                <div>
                    <livewire:gd-multople-upload-image wire:model="images" :max-images="$maxImages" :max-file-size="$maxImageSize"
                        :error-message="$errors->first('images')" />
                </div>


                <flux:field>
                    <flux:label>文章標題</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>

                <flux:field>
                    <flux:label>文章內容</flux:label>
                    @island(name: 'body-editor')
                        <livewire:gd-tinymce-editor wire:model="body" wire:key="body-editor" />
                    @endisland
                    <flux:error name="body" />
                </flux:field>

            </section>

            <!-- RIGHT SIDE -->
            <section class="w-full lg:w-80 lg:shrink-0">
                <div class="sticky top-10 space-y-4 ">
                    <!-- 語系 -->
                    <flux:field>
                        <flux:input.group>
                            <flux:input.group.prefix>語系</flux:input.group.prefix>
                            <flux:dropdown class="w-full">
                                <flux:button class="min-w-full" icon:trailing="chevron-down">
                                    {{ $locale }}
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.radio.group wire:model.live="locale">
                                        @foreach ($traitLocales as $key => $localeItem)
                                            <flux:menu.radio value="{{ $key }}">{{ $localeItem }}
                                            </flux:menu.radio>
                                        @endforeach
                                    </flux:menu.radio.group>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:input.group>
                        <flux:error name="locale" />
                    </flux:field>

                    <!-- 排序 -->
                    <flux:field>
                        <flux:input.group>
                            <flux:input.group.prefix>排序</flux:input.group.prefix>
                            <flux:input mask="99999" wire:model="sortNumber" />
                        </flux:input.group>
                        <flux:error name="sortNumber" />
                    </flux:field>


                    <flux:button type="submit" variant="primary" class="w-full">保存</flux:button>
                </div>
            </section>
        </form>
    </main>
</div>
