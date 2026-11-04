<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\ParameterBagUtils;

/**
 * Login success handler that ignores a posted target path (e.g. "_target_path")
 * unless it is a local path, so the login form cannot be used as an open redirect.
 * Other fallbacks (session target path, default target path) behave as usual.
 *
 * See https://symfony.com/doc/current/security/form_login.html#redirecting-after-success
 */
class LocalTargetPathSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    protected function determineTargetUrl(Request $request): string
    {
        $parameter = $this->options['target_path_parameter'];
        $targetPath = ParameterBagUtils::getRequestParameterValue($request, $parameter);

        if (null !== $targetPath && !LocalTargetPath::isLocal($targetPath)) {
            $root = explode('[', $parameter, 2)[0];
            $query = $request->query->all();
            $post = $request->request->all();
            $attributes = $request->attributes->all();
            unset($query[$root], $post[$root], $attributes[$root]);

            $request = $request->duplicate($query, $post, $attributes);
        }

        return parent::determineTargetUrl($request);
    }
}
