<?php

namespace App\Form;

use App\Entity\Billing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BillingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'label' => 'Date de début'
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date de fin'
            ])
            ->add('price', NumberType::class, [
                'required' => true,
                'label' => 'Prix',
                'scale' => 2
            ])
            ->add('paid', CheckboxType::class, [
                'required' => false,
                'label' => 'Payé'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Billing::class,
        ]);
    }
} 
 