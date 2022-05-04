<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateStart', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('dateEnd', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('summary', TextType::class)
        ;
    }
}
