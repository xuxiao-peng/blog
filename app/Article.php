<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\Pinyin\Pinyin;

/**
 * App\Article
 *
 * @property int $id
 * @property int $type
 * @property string $title
 * @property string $content
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $deleted_at
 * @property int $view_count
 * @property int $like_count
 * @property string $cover
 * @property string $abstract
 * @property-read mixed $info_url
 * @property-read object $next
 * @property-read string $opera_button
 * @property-read object $pre
 * @property-read string $update_button
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Article onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereAbstract($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereCover($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereLikeCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereViewCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Article withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Article withoutTrashed()
 * @property string $title_trans
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Article whereTitleTrans($value)
 */
class Article extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title', 'special_id', 'type', 'content',
    ];

    /**
     * 数据模型的启动方法
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->cover = get_cover($model->content);
            $model->abstract = get_abstract($model->content, $model->cover ? 220 : 360);
            $pinyin = (new Pinyin())->convert($model->title);
            $pinyin = implode('-', $pinyin);
            $model->title_trans = $pinyin;
        });
    }

    /**
     * 获取下一篇文章
     * @return object
     */
    public function getNextAttribute()
    {
        return $this->where('type', 1)->where('id', '>', $this->id)->orderBy('id', 'asc')->first(['id', 'title', 'title_trans']);
    }

    /**
     * 获取上一篇文章
     * @return object
     */
    public function getPreAttribute()
    {
        return $this->where('type', 1)->where('id', '<', $this->id)->orderBy('id', 'desc')->first(['id', 'title', 'title_trans']);
    }

    /**获取详情链接
     * @return string
     */
    public function getInfoUrlAttribute()
    {
        return route('blog.show', ['id' => $this->id, 'title' => $this->title_trans]);
    }

    /**
     * 返回操作按钮
     * @return string
     */
    public function getOperaButtonAttribute()
    {
        return $this->getUpdateButtonAttribute() . $this->getPublishButtonAttribute() . $this->getDeleteButtonAttribute();
    }

    /**
     * 更新按钮
     * @return string
     */
    private function getUpdateButtonAttribute()
    {
        $url = route('admin.blog.edit', ['id' => $this->id]);
        return '<div style="display:inline-block;"><a class="btn btn-sm btn-primary" href="' . $url . '">更新文章</a></div>&emsp;';
    }

    /**
     * 删除按钮
     * @return string
     */
    private function getDeleteButtonAttribute()
    {
        $url = route('admin.blog.delete', ['id' => $this->id]);
        return sprintf('<form action="%s" method="POST" 
                    style="display: inline-block;">
                      %s %s
                    <input type="submit"
                           class="btn btn-sm btn-danger"
                           value="删除文章"
                           onclick="return confirm(%s);">
                </form>&emsp;', $url, method_field('DELETE'), csrf_field(), "'确定要删除吗？'");
    }

    /**
     * 发布按钮
     * @return string
     */
    private function getPublishButtonAttribute()
    {
        $url = route('admin.blog.settingType', ['id' => $this->id]);
        return sprintf('<form action="%s" method="POST" 
                    style="display: inline-block;">
                      %s %s
                    <input type="hidden" name="type" value="%d">
                    <input type="submit"
                           class="btn btn-sm btn-default"
                           value="%s">
                </form>&emsp;',
            $url,
            method_field('PUT'),
            csrf_field(),
            ($this->type == 1) ? 2 : 1,
            ($this->type == 1) ? '不发布' : '发布'
        );
    }


    /**
     * 关联查询评论信息
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(ArticleComment::class, 'aid')->orderBy('created_at', 'asc');
    }

}
