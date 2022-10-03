<?php

/**
 *  Copyright (C) 2022 Karsten Lehmann <mail@kalehmann.de>
 *
 *  This file is part of unlocked-server.
 *
 *  unlocked-server is free software: you can redistribute it and/or
 *  modify it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, version 3 of the License.
 *
 *  unlocked-server is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with unlocked-server. If not, see
 *  <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace KaLehmann\UnlockedServer\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchKeyType extends AbstractType
{
    /**
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'query',
                TextType::class,
                [
                    'label' => 'key.search.query',
                    'label_attr' => [
                        'class' => 'inline',
                    ],
                    'required' => false,
                ],
            )
            ->add(
                'showDeleted',
                CheckboxType::class,
                [
                    'label' => 'key.search.show_deleted',
                    'label_attr' => [
                        'class' => 'button inline',
                    ],
                    'required' => false,
                ],
            )
            ->add(
                'search',
                SubmitType::class,
                [
                    'attr' => [
                        'class' => 'inline',
                        'name' => false,
                    ],
                    'label' => 'key.search.search',
                ],
            )
            ->setMethod('GET');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('attr', ['class' => 'inline']);
        $resolver->setDefault('csrf_protection', false);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
