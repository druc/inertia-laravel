<?php

namespace Inertia;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Arrayable;

class ResponseFactory
{
    use Macroable;

    protected $rootView = 'app';
    protected $sharedProps = [];
    protected $version = null;
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function setRootView($name)
    {
        $this->rootView = $name;
    }

    public function share($key, $value = null)
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            Arr::set($this->sharedProps, $key, $value);
        }
    }

    public function getShared($key = null)
    {
        if ($key) {
            return Arr::get($this->sharedProps, $key);
        }

        return $this->sharedProps;
    }

    public function version($version)
    {
        $this->version = $version;
    }

    public function getVersion()
    {
        $version = $this->version instanceof Closure
            ? App::call($this->version)
            : $this->version;

        return (string) $version;
    }

    public function decorate($response, $data, $redirect)
    {
        if ($this->request->hasHeader('X-Inertia')) {
            $only = array_filter(explode(',', $this->request->header('X-Inertia-Partial-Data')));

            if (!$this->request->hasHeader('X-Inertia-Partial-Data')) {
                $only = array_unique(
                    array_merge(
                        array_keys($this->sharedProps),
                        array_keys($data)
                    )
                );
            }

            $this->request->headers->set('X-Inertia-Partial-Data', implode(',', $only));
            $this->request->headers->set('X-Inertia-Partial-Component', $response->getComponent());
        }

        $redirect = $this->request->header('X-Inertia-Render-Url', $redirect);

        return $response->with($data)->renderUrl($redirect);
    }

    public function back()
    {
        if ($this->request->hasHeader('X-Inertia-Render-Url')) {
            return redirect($this->request->header('X-Inertia-Render-Url'));
        }

        return redirect()->back();
    }

    public function render($component, $props = [])
    {
        if ($props instanceof Arrayable) {
            $props = $props->toArray();
        }

        return new Response(
            $component,
            array_merge($this->sharedProps, $props),
            $this->rootView,
            $this->getVersion()
        );
    }
}